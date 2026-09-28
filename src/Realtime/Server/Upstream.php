<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Request;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Proto\Realtime\RealtimeBridge;

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
 * @package Proto\Realtime\Server
 */
final class Upstream
{
	/**
	 * @var HttpClient
	 */
	private HttpClient $client;

	/**
	 * @param RealtimeConfig $config
	 * @param Metrics $metrics
	 * @param HttpClient|null $client
	 */
	public function __construct(
		private readonly RealtimeConfig $config,
		private readonly Metrics $metrics,
		?HttpClient $client = null
	)
	{
		$this->client = $client ?? $this->buildClient();
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
			$response = $this->client->request($request);
			$status = $response->getStatus();
			$body = $response->getBody()->buffer();
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
		$request = $this->request($origin, RealtimeBridge::MODE_HYDRATE);
		$request->setBody((string)json_encode(['channel' => $channel, 'message' => $message]));
		$request->setHeader('content-type', 'application/json');

		$started = microtime(true);
		try
		{
			$response = $this->client->request($request);
			$status = $response->getStatus();
			$body = $response->getBody()->buffer();
		}
		catch (\Throwable)
		{
			$this->metrics->hydrateFailures++;
			return null;
		}
		finally
		{
			$this->metrics->recordHydrate(microtime(true) - $started);
		}

		$decoded = json_decode($body, true);
		if ($status !== 200 || !is_array($decoded) || !array_key_exists('result', $decoded))
		{
			$this->metrics->hydrateFailures++;
			return null;
		}

		return [
			'result' => $decoded['result'],
			'close' => (bool)($decoded['close'] ?? false)
		];
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
