<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\DeferredFuture;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Middleware\ForwardedHeaderType;
use Amp\Http\Server\SocketHttpServer;
use Amp\Redis\RedisSubscriber;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop\UnsupportedFeatureException;
use function Amp\Redis\createRedisClient;
use function Amp\Redis\createRedisConnector;
use function Amp\trapSignal;

/**
 * RealtimeServer
 *
 * Long-running SSE server for Proto apps. Holds thousands of idle
 * browser streams in one process and fans out Redis pub/sub to them.
 * Access checks and per-message logic stay in the PHP app (see
 * `RealtimeBridge`); this process never reads the session store or the
 * database. Start one per CPU core behind the proxy.
 *
 * Run: `vendor/bin/proto-realtime` from the app root.
 *
 * @package Proto\Realtime\Server
 */
final class RealtimeServer
{
	/**
	 * @param RealtimeConfig $config
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private readonly RealtimeConfig $config,
		private readonly LoggerInterface $logger
	)
	{
	}

	/**
	 * @var SocketHttpServer|null
	 */
	private ?SocketHttpServer $server = null;

	/**
	 * @var SseRequestHandler|null
	 */
	private ?SseRequestHandler $handler = null;

	/**
	 * Serve until SIGINT / SIGTERM, then drain and stop.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->start();

		try
		{
			$signal = trapSignal(defined('SIGINT') ? [SIGINT, SIGTERM] : [2, 15]);
		}
		catch (UnsupportedFeatureException)
		{
			// Signals need ext-pcntl (or ev/uv). Serve without a graceful
			// drain: on stop, browsers reconnect after their retry delay.
			$this->logger->warning('Signal handling unavailable (install ext-pcntl for graceful drain); serving until killed.');
			(new DeferredFuture())->getFuture()->await();
			return;
		}

		$this->logger->info('Draining realtime server', ['signal' => $signal]);
		$this->stop();
	}

	/**
	 * Start listening without blocking (tests, embedding).
	 *
	 * @return string The bound address, e.g. 127.0.0.1:9100
	 */
	public function start(): string
	{
		$metrics = new Metrics();
		$hub = new Hub(
			new RedisSubscriber(createRedisConnector($this->config->redisUri)),
			$metrics,
			$this->logger
		);
		$registry = new ConnectionRegistry(createRedisClient($this->config->redisUri), $this->logger);
		$upstream = new Upstream($this->config, $metrics);
		$handler = new SseRequestHandler($this->config, $hub, $upstream, $registry, $metrics, $this->logger);

		// No compression (it buffers SSE frames) and no concurrency limit
		// (every open stream is a "concurrent request").
		$server = SocketHttpServer::createForBehindProxy(
			$this->logger,
			ForwardedHeaderType::XForwardedFor,
			$this->config->trustedProxies,
			enableCompression: false,
			concurrencyLimit: null
		);
		$server->expose($this->config->host . ':' . $this->config->port);
		$server->start($handler, new DefaultErrorHandler());
		$this->server = $server;
		$this->handler = $handler;

		$address = $server->getServers()[0]->getAddress()->toString();
		$this->logger->info('Realtime server listening', [
			'address' => $address,
			'upstream' => $this->config->upstream
		]);

		return $address;
	}

	/**
	 * Ask every browser to reconnect soon, then stop listening.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		$this->handler?->drain();
		$this->server?->stop();
		$this->server = null;
		$this->handler = null;
	}
}
