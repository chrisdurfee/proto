<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Http\Server\Middleware\Forwarded;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Psr\Log\LoggerInterface;

/**
 * SseRequestHandler
 *
 * Every `GET` is a stream request: authorize with PHP, then stream.
 * Two internal paths sit beside it:
 *
 *  - `/__realtime/health`: liveness for Docker and the proxy.
 *  - `/__realtime/metrics`: counters, only with the shared secret.
 *
 * @package Proto\Realtime\Server
 */
final class SseRequestHandler implements RequestHandler
{
	/**
	 * @var array<int, Connection>
	 */
	private array $connections = [];

	/**
	 * @var bool
	 */
	private bool $draining = false;

	/**
	 * Streams open or still authorizing.
	 *
	 * @var int
	 */
	private int $slots = 0;

	/**
	 * Streams open or authorizing, per client IP.
	 *
	 * @var array<string, int>
	 */
	private array $slotsByIp = [];

	/**
	 * @param RealtimeConfig $config
	 * @param Hub $hub
	 * @param Upstream $upstream
	 * @param ConnectionRegistry $registry
	 * @param Metrics $metrics
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private readonly RealtimeConfig $config,
		private readonly Hub $hub,
		private readonly Upstream $upstream,
		private readonly ConnectionRegistry $registry,
		private readonly Metrics $metrics,
		private readonly LoggerInterface $logger
	)
	{
	}

	/**
	 * @param Request $request
	 * @return Response
	 */
	public function handleRequest(Request $request): Response
	{
		$path = $request->getUri()->getPath();
		if ($path === '/__realtime/health')
		{
			return $this->json($this->draining ? 503 : 200, ['status' => $this->draining ? 'draining' : 'ok']);
		}

		if ($path === '/__realtime/metrics')
		{
			$sent = (string)($request->getHeader('x-proto-realtime') ?? '');
			if (!hash_equals($this->config->secret, $sent))
			{
				return $this->json(403, ['message' => 'Forbidden']);
			}

			return $this->json(200, $this->metrics->snapshot($this->hub->channelCount()));
		}

		if ($request->getMethod() !== 'GET')
		{
			return $this->json(405, ['message' => 'Method not allowed']);
		}

		if ($this->draining)
		{
			return $this->json(503, ['message' => 'Restarting'], ['retry-after' => '1']);
		}

		/**
		 * Cap streams per process and per client before calling PHP:
		 * every connect costs an upstream authorize, so an unauthenticated
		 * flood would otherwise queue without bound behind the upstream
		 * semaphore and starve legitimate connects.
		 */
		$ip = self::clientIp($request);
		if (!$this->acquireSlot($ip))
		{
			return $this->json(503, ['message' => 'Too many streams'], ['retry-after' => (string)random_int(2, 10)]);
		}

		try
		{
			$origin = OriginRequest::fromServerRequest($request);
			$result = $this->upstream->authorize($origin);
		}
		catch (\Throwable $e)
		{
			$this->releaseSlot($ip);
			$this->logger->error('Authorize failed', ['error' => $e->getMessage()]);
			return $this->json(503, ['message' => 'Stream unavailable'], ['retry-after' => '3']);
		}

		if ($result->descriptor === null)
		{
			$this->releaseSlot($ip);
			return new Response($result->status, ['content-type' => $result->contentType], $result->body);
		}

		$connection = new Connection(
			$result->descriptor,
			$origin,
			$this->hub,
			$this->upstream,
			$this->registry,
			$this->config,
			$this->metrics,
			$this->logger,
			function (Connection $closed) use ($ip): void
			{
				if (isset($this->connections[$closed->id]))
				{
					unset($this->connections[$closed->id]);
				}
				$this->releaseSlot($ip);
			}
		);

		try
		{
			$response = $connection->open();
		}
		catch (\Throwable $e)
		{
			$connection->close('open failed');
			$this->logger->error('Could not open stream', ['error' => $e->getMessage()]);
			return $this->json(503, ['message' => 'Stream unavailable'], ['retry-after' => '3']);
		}

		$this->connections[$connection->id] = $connection;
		return $response;
	}

	/**
	 * Reserves a stream slot for a client, or returns false when the
	 * process or that client is at its limit.
	 *
	 * @param string $ip
	 * @return bool
	 */
	private function acquireSlot(string $ip): bool
	{
		if ($this->slots >= $this->config->maxConnections || ($this->slotsByIp[$ip] ?? 0) >= $this->config->maxConnectionsPerIp)
		{
			return false;
		}

		$this->slots++;
		$this->slotsByIp[$ip] = ($this->slotsByIp[$ip] ?? 0) + 1;
		return true;
	}

	/**
	 * @param string $ip
	 * @return void
	 */
	private function releaseSlot(string $ip): void
	{
		$this->slots = max(0, $this->slots - 1);
		$count = ($this->slotsByIp[$ip] ?? 0) - 1;
		if ($count > 0)
		{
			$this->slotsByIp[$ip] = $count;
			return;
		}

		unset($this->slotsByIp[$ip]);
	}

	/**
	 * Client IP as resolved by the trusted-proxy middleware, else the peer.
	 *
	 * @param Request $request
	 * @return string
	 */
	private static function clientIp(Request $request): string
	{
		if ($request->hasAttribute(Forwarded::class))
		{
			$forwarded = $request->getAttribute(Forwarded::class);
			if ($forwarded instanceof Forwarded)
			{
				return $forwarded->getFor()->getAddress();
			}
		}

		return OriginRequest::remoteIp($request->getClient()->getRemoteAddress()->toString());
	}

	/**
	 * Stop taking streams and ask every browser to reconnect soon (to
	 * the next process, after a deploy).
	 *
	 * @return void
	 */
	public function drain(): void
	{
		$this->draining = true;
		foreach ($this->connections as $connection)
		{
			$connection->close('server restarting', true);
		}
	}

	/**
	 * @param int $status
	 * @param array<string, mixed> $body
	 * @param array<string, string> $headers
	 * @return Response
	 */
	private function json(int $status, array $body, array $headers = []): Response
	{
		return new Response(
			$status,
			['content-type' => 'application/json', 'cache-control' => 'no-store'] + $headers,
			(string)json_encode($body)
		);
	}
}
