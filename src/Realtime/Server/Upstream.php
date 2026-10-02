<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Request;
use Amp\Socket\ClientTlsContext;
use Amp\Http\Client\Response;
use Amp\Socket\ConnectContext;
use Amp\Sync\LocalSemaphore;
use Proto\Realtime\RealtimeBridge;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Upstream
 *
 * Calls back into the PHP app with the browser's own request plus the
 * realtime secret. PHP answers from `RealtimeBridge`:
 *
 *  - authorize: a `StreamDescriptor`, or a normal error (403, 404, ...)
 *    that is relayed to the browser unchanged.
 *  - hydrate: the controller callback's result for one message.
 *
 * At most `upstreamConcurrency` calls run at once, so a burst of messages
 * (or a reconnect wave) queues here instead of flooding PHP-FPM. Hydrate
 * calls past `upstreamMaxQueued` are dropped and counted as shed.
 *
 * @package Proto\Realtime\Server
 */
final class Upstream
{
	/**
	 * @var HttpClient
	 */
	private HttpClient $client;

	/**
	 * @var LocalSemaphore
	 */
	private LocalSemaphore $slots;

	/**
	 * @var LoggerInterface
	 */
	private LoggerInterface $logger;

	/**
	 * @param RealtimeConfig $config
	 * @param Metrics $metrics
	 * @param HttpClient|null $client
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		private readonly RealtimeConfig $config,
		private readonly Metrics $metrics,
		?HttpClient $client = null,
		?LoggerInterface $logger = null
	)
	{
		$this->client = $client ?? $this->buildClient();
		$this->logger = $logger ?? new NullLogger();
		$this->slots = new LocalSemaphore($config->upstreamConcurrency);
	}

	/**
	 * @param OriginRequest $origin
	 * @return AuthorizeResult
	 */
	public function authorize(OriginRequest $origin): AuthorizeResult
	{
		$request = $this->request($origin, RealtimeBridge::MODE_AUTHORIZE);

		try
		{
			[$response, $body] = $this->send($request);
			$status = $response->getStatus();
		}
		catch (\Throwable $e)
		{
			$this->metrics->upstreamErrors++;
			return AuthorizeResult::failed(502, 'Realtime upstream unavailable.');
		}

		if ($status === 200)
		{
			$descriptor = StreamDescriptor::fromArray(json_decode($body, true));
			if ($descriptor !== null)
			{
				return AuthorizeResult::allowed($descriptor);
			}
		}

		// Not a stream endpoint, or PHP refused: relay PHP's own answer.
		return AuthorizeResult::failed(
			$status === 200 ? 403 : $status,
			$body,
			$response->getHeader('content-type') ?? 'application/json'
		);
	}

	/**
	 * Run the controller's per-message callback in PHP.
	 *
	 * @param OriginRequest $origin
	 * @param string $channel
	 * @param string $message Raw published string.
	 * @return array{result: mixed, close: bool}|null Null when PHP failed.
	 */
	public function hydrate(OriginRequest $origin, string $channel, string $message): ?array
	{
		if ($this->metrics->upstreamQueued >= $this->config->upstreamMaxQueued
			&& $this->metrics->upstreamInFlight >= $this->config->upstreamConcurrency)
		{
			$this->metrics->hydrateShed++;
			return null;
		}

		$request = $this->request($origin, RealtimeBridge::MODE_HYDRATE);
		$request->setBody((string)json_encode(['channel' => $channel, 'message' => $message]));
		$request->setHeader('content-type', 'application/json');

		$started = microtime(true);
		try
		{
			[$response, $body] = $this->send($request);
			$status = $response->getStatus();
		}
		catch (\Throwable $e)
		{
			$this->hydrateFailed($origin, $channel, 0, get_debug_type($e) . ': ' . $e->getMessage());
			return null;
		}
		finally
		{
			$this->metrics->recordHydrate(microtime(true) - $started);
		}

		$decoded = json_decode($body, true);
		if ($status !== 200 || !is_array($decoded) || !array_key_exists('result', $decoded))
		{
			$this->hydrateFailed($origin, $channel, $status, 'unexpected body: ' . substr(trim($body), 0, 160));
			return null;
		}

		return [
			'result' => $decoded['result'],
			'close' => (bool)($decoded['close'] ?? false)
		];
	}

	/**
	 * Send one request once a slot is free, buffering the whole body
	 * before the slot is released.
	 *
	 * @param Request $request
	 * @return array{0: Response, 1: string}
	 */
	private function send(Request $request): array
	{
		$this->metrics->upstreamQueued++;
		$this->metrics->upstreamQueuedPeak = max($this->metrics->upstreamQueuedPeak, $this->metrics->upstreamQueued);
		try
		{
			$lock = $this->slots->acquire();
		}
		finally
		{
			$this->metrics->upstreamQueued--;
		}

		$this->metrics->upstreamInFlight++;
		try
		{
			$response = $this->client->request($request);
			return [$response, $response->getBody()->buffer()];
		}
		finally
		{
			$this->metrics->upstreamInFlight--;
			$lock->release();
		}
	}

	/**
	 * Count and log one dropped message. The path is logged without its
	 * query string, and never the cookie, so nothing identifies a user.
	 *
	 * @param OriginRequest $origin
	 * @param string $channel
	 * @param int $status 0 when the request itself failed
	 * @param string $reason
	 * @return void
	 */
	private function hydrateFailed(OriginRequest $origin, string $channel, int $status, string $reason): void
	{
		$this->metrics->hydrateFailures++;
		$this->logger->warning('Hydrate failed; message dropped for this stream', [
			'path' => $origin->path,
			'channel' => $channel,
			'status' => $status,
			'reason' => $reason
		]);
	}

	/**
	 * @param OriginRequest $origin
	 * @param string $mode
	 * @return Request
	 */
	private function request(OriginRequest $origin, string $mode): Request
	{
		$request = new Request($this->config->upstream . $origin->target(), 'GET');
		$request->setProtocolVersions(['1.1']);
		$request->setTransferTimeout($this->config->upstreamTimeoutSeconds);
		$request->setInactivityTimeout($this->config->upstreamTimeoutSeconds);
		$request->setBodySizeLimit(4 * 1024 * 1024);

		foreach ($origin->headers as $name => $value)
		{
			$request->setHeader($name, $value);
		}

		$request->setHeader('accept', 'application/json');
		$request->setHeader('x-proto-realtime', $this->config->secret);
		$request->setHeader('x-proto-realtime-mode', $mode);
		return $request;
	}

	/**
	 * @return HttpClient
	 */
	private function buildClient(): HttpClient
	{
		$builder = new HttpClientBuilder();
		$builder = $builder->retry(0)->followRedirects(0);
		if (!$this->config->upstreamVerifyTls)
		{
			$context = (new ConnectContext())->withTlsContext(
				(new ClientTlsContext(''))->withoutPeerVerification()
			);
			$builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $context)));
		}

		return $builder->build();
	}
}
