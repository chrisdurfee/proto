<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Future;
use Amp\Redis\RedisSubscriber;
use Amp\Redis\RedisSubscription;
use Psr\Log\LoggerInterface;
use function Amp\async;

/**
 * Hub
 *
 * One Redis subscription and one listener per channel, fanned out to
 * every local connection on that channel. The first connection on a
 * channel subscribes; the last one to leave unsubscribes.
 *
 * `shared` streams are hydrated once per message per distinct stream URL
 * and the result goes to every connection on that URL.
 *
 * @package Proto\Realtime\Server
 */
final class Hub
{
	/**
	 * @var array<string, array<int, Connection>>
	 */
	private array $members = [];

	/**
	 * @var array<string, RedisSubscription>
	 */
	private array $subscriptions = [];

	/**
	 * @param RedisSubscriber $subscriber
	 * @param Metrics $metrics
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private readonly RedisSubscriber $subscriber,
		private readonly Metrics $metrics,
		private readonly LoggerInterface $logger
	)
	{
	}

	/**
	 * @param string $channel
	 * @param Connection $connection
	 * @return void
	 */
	public function join(string $channel, Connection $connection): void
	{
		$this->members[$channel][$connection->id] = $connection;
		if (isset($this->subscriptions[$channel]))
		{
			return;
		}

		try
		{
			$subscription = $this->subscriber->subscribe($channel);
		}
		catch (\Throwable $e)
		{
			unset($this->members[$channel][$connection->id]);
			if (($this->members[$channel] ?? []) === [])
			{
				unset($this->members[$channel]);
			}
			throw $e;
		}

		$this->subscriptions[$channel] = $subscription;
		async(fn() => $this->listen($channel, $subscription))->ignore();
	}

	/**
	 * @param string $channel
	 * @param Connection $connection
	 * @return void
	 */
	public function leave(string $channel, Connection $connection): void
	{
		unset($this->members[$channel][$connection->id]);
		if (($this->members[$channel] ?? []) !== [])
		{
			return;
		}

		unset($this->members[$channel]);
		$subscription = $this->subscriptions[$channel] ?? null;
		unset($this->subscriptions[$channel]);

		try
		{
			$subscription?->unsubscribe();
		}
		catch (\Throwable)
		{
			// Already gone with the Redis connection.
		}
	}

	/**
	 * Deliver one published message to every connection on the channel.
	 *
	 * @param string $channel
	 * @param string $message
	 * @return void
	 */
	public function dispatch(string $channel, string $message): void
	{
		$this->metrics->messagesReceived++;

		/** @var array<string, Future> $shared */
		$shared = [];
		foreach ($this->members[$channel] ?? [] as $connection)
		{
			if ($connection->isShared() && !$connection->isControlChannel($channel))
			{
				$key = $connection->groupKey();
				$shared[$key] ??= $connection->hydrate($channel, $message);
				$connection->enqueue($shared[$key]);
				continue;
			}

			$connection->receive($channel, $message);
		}
	}

	/**
	 * @return int
	 */
	public function channelCount(): int
	{
		return count($this->subscriptions);
	}

	/**
	 * @param string $channel
	 * @param RedisSubscription $subscription
	 * @return void
	 */
	private function listen(string $channel, RedisSubscription $subscription): void
	{
		try
		{
			foreach ($subscription as $message)
			{
				$this->dispatch($channel, (string)$message);
			}
		}
		catch (\Throwable $e)
		{
			// leave() unsubscribing disposes the iterator; that is expected.
			if (($this->subscriptions[$channel] ?? null) === $subscription)
			{
				$this->logger->warning('Redis subscription ended with an error', [
					'channel' => $channel,
					'error' => $e->getMessage()
				]);
			}
		}

		// Ended while still ours means Redis dropped us. Close the streams
		// so browsers reconnect and resubscribe instead of going silent.
		if (($this->subscriptions[$channel] ?? null) === $subscription)
		{
			unset($this->subscriptions[$channel]);
			foreach ($this->members[$channel] ?? [] as $connection)
			{
				$connection->close('redis subscription lost', true);
			}
		}
	}
}
