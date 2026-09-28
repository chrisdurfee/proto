<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\ByteStream\ReadableIterableStream;
use Amp\Future;
use Amp\Http\Server\Response;
use Amp\Pipeline\Queue;
use Proto\Realtime\RealtimeBridge;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use function Amp\async;

/**
 * Connection
 *
 * One browser SSE stream. Messages are hydrated (or forwarded raw) and
 * written strictly in publish order through a per-connection chain. A
 * client that stops reading is dropped once `maxPendingWrites` frames
 * back up, so a slow phone cannot hold memory for everyone else.
 *
 * @package Proto\Realtime\Server
 */
final class Connection
{
	/**
	 * @var int
	 */
	private static int $sequence = 0;

	/**
	 * Local id (hub membership key).
	 *
	 * @var int
	 */
	public readonly int $id;

	/**
	 * Global id, same shape as `RedisServerEvents` connection ids.
	 *
	 * @var string
	 */
	public readonly string $connectionId;

	/**
	 * @var Queue<string>
	 */
	private Queue $queue;

	/**
	 * @var bool
	 */
	private bool $closed = false;

	/**
	 * Frames handed to the socket but not yet written.
	 *
	 * @var int
	 */
	private int $pendingWrites = 0;

	/**
	 * Messages waiting on hydration, in order.
	 *
	 * @var int
	 */
	private int $backlog = 0;

	/**
	 * Tail of the in-order delivery chain.
	 *
	 * @var Future
	 */
	private Future $tail;

	/**
	 * @var array<int, string>
	 */
	private array $timers = [];

	/**
	 * @var array<int, string>
	 */
	private array $joined = [];

	/**
	 * @var string
	 */
	private string $closeChannel;

	/**
	 * @var string|null
	 */
	private ?string $userCloseChannel;

	/**
	 * @var string
	 */
	private string $registryKey;

	/**
	 * @param StreamDescriptor $descriptor
	 * @param OriginRequest $origin
	 * @param Hub $hub
	 * @param Upstream $upstream
	 * @param ConnectionRegistry $registry
	 * @param RealtimeConfig $config
	 * @param Metrics $metrics
	 * @param LoggerInterface $logger
	 * @param \Closure|null $onClose Called once with this connection.
	 */
	public function __construct(
		private StreamDescriptor $descriptor,
		private readonly OriginRequest $origin,
		private readonly Hub $hub,
		private readonly Upstream $upstream,
		private readonly ConnectionRegistry $registry,
		private readonly RealtimeConfig $config,
		private readonly Metrics $metrics,
		private readonly LoggerInterface $logger,
		private readonly ?\Closure $onClose = null
	)
	{
		$this->id = ++self::$sequence;
		$this->connectionId = uniqid('sse_', true);
		$this->queue = new Queue();
		$this->tail = Future::complete();
		$this->closeChannel = "sse:close:{$this->connectionId}";
		$this->userCloseChannel = $descriptor->hasUser() ? "sse:user:close:{$descriptor->userId}" : null;
		$this->registryKey = ConnectionRegistry::key($descriptor, $origin->path, $this->connectionId);
	}

	/**
	 * Start streaming. Returns the response the HTTP server sends.
	 *
	 * @return Response
	 */
	public function open(): Response
	{
		$response = new Response(200, [
			'content-type' => 'text/event-stream',
			'cache-control' => 'no-cache, no-transform',
			'x-accel-buffering' => 'no',
			'connection' => 'keep-alive'
		], new ReadableIterableStream($this->queue->pipe()));

		$this->write(SseFormat::retry($this->config->retryMilliseconds));
		$this->write(SseFormat::comment('connected'));

		foreach ($this->channels() as $channel)
		{
			$this->hub->join($channel, $this);
			$this->joined[] = $channel;
		}

		$ttl = $this->config->maxDurationSeconds + 60;
		async(fn() => $this->registry->claim($this->registryKey, $this->connectionId, $ttl))->ignore();

		$this->timers[] = EventLoop::repeat(
			$this->config->heartbeatSeconds,
			fn() => $this->write(SseFormat::comment('heartbeat'))
		);
		$this->timers[] = EventLoop::delay(
			$this->config->maxDurationSeconds,
			fn() => $this->close('max duration', true)
		);
		$this->timers[] = EventLoop::repeat(
			$this->config->reauthorizeSeconds,
			fn() => $this->reauthorize()
		);

		$this->metrics->connectionsOpen++;
		$this->metrics->connectionsTotal++;
		return $response;
	}

	/**
	 * @return bool
	 */
	public function isShared(): bool
	{
		return $this->descriptor->hydrate === RealtimeBridge::HYDRATE_SHARED;
	}

	/**
	 * Streams with the same URL get the same shared hydration result.
	 *
	 * @return string
	 */
	public function groupKey(): string
	{
		return $this->origin->target();
	}

	/**
	 * @param string $channel
	 * @return bool
	 */
	public function isControlChannel(string $channel): bool
	{
		return $channel === $this->closeChannel || $channel === $this->userCloseChannel;
	}

	/**
	 * A published message for this connection alone.
	 *
	 * @param string $channel
	 * @param string $message
	 * @return void
	 */
	public function receive(string $channel, string $message): void
	{
		if ($this->isControlChannel($channel))
		{
			$this->close('close signal');
			return;
		}

		$this->enqueue($this->hydrate($channel, $message));
	}

	/**
	 * Start hydrating one message. Raw streams skip PHP entirely.
	 *
	 * @param string $channel
	 * @param string $message
	 * @return Future<array{result: mixed, close: bool}|null>
	 */
	public function hydrate(string $channel, string $message): Future
	{
		if ($this->descriptor->hydrate === RealtimeBridge::HYDRATE_RAW)
		{
			return Future::complete([
				'result' => json_decode($message, true) ?? $message,
				'close' => false
			]);
		}

		return async(fn() => $this->upstream->hydrate($this->origin, $channel, $message));
	}

	/**
	 * Queue a hydration result behind every earlier one.
	 *
	 * @param Future $pending
	 * @return void
	 */
	public function enqueue(Future $pending): void
	{
		if ($this->closed)
		{
			return;
		}

		if (++$this->backlog > $this->config->maxPendingWrites)
		{
			$this->metrics->slowClientsDropped++;
			$this->close('too far behind', true);
			return;
		}

		$previous = $this->tail;
		$this->tail = async(function () use ($previous, $pending): void
		{
			try
			{
				$previous->await();
			}
			catch (\Throwable)
			{
				// Earlier failures were already handled in their own step.
			}

			try
			{
				$out = $pending->await();
			}
			catch (\Throwable)
			{
				$out = null;
			}

			$this->backlog--;
			$this->deliver($out);
		});
	}

	/**
	 * Close the stream. With `$retrySoon`, the browser reconnects after
	 * one second instead of its default delay.
	 *
	 * @param string $reason
	 * @param bool $retrySoon
	 * @return void
	 */
	public function close(string $reason, bool $retrySoon = false): void
	{
		if ($this->closed)
		{
			return;
		}

		if ($retrySoon)
		{
			$this->push(SseFormat::retry(1000));
		}

		$this->closed = true;
		foreach ($this->timers as $timer)
		{
			EventLoop::cancel($timer);
		}
		$this->timers = [];

		foreach ($this->joined as $channel)
		{
			$this->hub->leave($channel, $this);
		}
		$this->joined = [];

		async(fn() => $this->registry->release($this->registryKey, $this->connectionId))->ignore();

		try
		{
			$this->queue->complete();
		}
		catch (\Throwable)
		{
			// Already completed or disposed by the client going away.
		}

		$this->metrics->connectionsOpen--;
		$this->logger->debug('Stream closed', ['connection' => $this->connectionId, 'reason' => $reason]);
		if ($this->onClose !== null)
		{
			($this->onClose)($this);
		}
	}

	/**
	 * @param array{result: mixed, close: bool}|null $out
	 * @return void
	 */
	private function deliver(?array $out): void
	{
		if ($this->closed || $out === null)
		{
			return;
		}

		if ($out['close'])
		{
			$this->close('closed by handler');
			return;
		}

		if ($out['result'] === null)
		{
			return;
		}

		$json = SseFormat::encode($out['result']);
		if ($json !== null)
		{
			$this->write(SseFormat::message($json));
		}
	}

	/**
	 * Every channel this connection listens on, including its own
	 * close channel and the user-wide kick channel.
	 *
	 * @return array<int, string>
	 */
	private function channels(): array
	{
		$channels = $this->descriptor->channels;
		$channels[] = $this->closeChannel;
		if ($this->userCloseChannel !== null)
		{
			$channels[] = $this->userCloseChannel;
		}

		return array_values(array_unique($channels));
	}

	/**
	 * Re-check access. Revoked access closes the stream; changed channel
	 * lists (someone joined a conversation) are applied in place.
	 *
	 * @return void
	 */
	private function reauthorize(): void
	{
		if ($this->closed)
		{
			return;
		}

		$result = $this->upstream->authorize($this->origin);
		if ($this->closed)
		{
			return;
		}

		$descriptor = $result->descriptor;
		if ($descriptor === null || $descriptor->userId !== $this->descriptor->userId)
		{
			// 5xx is an upstream blip, not a revocation; try again next round.
			if ($descriptor === null && $result->status >= 500)
			{
				return;
			}

			$this->close('access revoked', true);
			return;
		}

		$this->descriptor = $descriptor;
		$wanted = $this->channels();
		foreach (array_diff($wanted, $this->joined) as $channel)
		{
			$this->hub->join($channel, $this);
		}
		foreach (array_diff($this->joined, $wanted) as $channel)
		{
			$this->hub->leave($channel, $this);
		}
		$this->joined = $wanted;
	}

	/**
	 * Write one frame, dropping clients that stopped reading.
	 *
	 * @param string $frame
	 * @return void
	 */
	private function write(string $frame): void
	{
		if ($this->closed)
		{
			return;
		}

		if ($this->pendingWrites >= $this->config->maxPendingWrites)
		{
			$this->metrics->slowClientsDropped++;
			$this->close('client not reading');
			return;
		}

		$this->push($frame);
	}

	/**
	 * @param string $frame
	 * @return void
	 */
	private function push(string $frame): void
	{
		$this->pendingWrites++;
		try
		{
			$this->queue->pushAsync($frame)
				->map(function (): void
				{
					$this->pendingWrites--;
					$this->metrics->framesSent++;
				})
				->catch(function (): void
				{
					// The client went away and the body stream was disposed.
					$this->pendingWrites--;
					$this->close('client gone');
				})
				->ignore();
		}
		catch (\Throwable)
		{
			$this->pendingWrites--;
			$this->close('client gone');
		}
	}
}
