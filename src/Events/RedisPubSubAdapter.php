<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Cache\Drivers\RedisDriver;

/**
 * RedisPubSubAdapter
 *
 * Bridges Redis pub/sub functionality with the global Events system,
 * allowing seamless integration between local and distributed events.
 *
 * @package Proto\Events
 */
class RedisPubSubAdapter
{
	/**
	 * @var RedisDriver The Redis driver instance.
	 */
	protected RedisDriver $redis;

	/**
	 * @var array Active subscriptions with their channels and callbacks.
	 */
	protected array $subscriptions = [];

	/**
	 * @var bool Whether the adapter is actively listening.
	 */
	protected bool $listening = false;

	/**
	 * Constructor.
	 *
	 * @param RedisDriver $redis The Redis driver instance.
	 */
	public function __construct(RedisDriver $redis)
	{
		$this->redis = $redis;
	}

	/**
	 * Publishes a message to a Redis channel.
	 *
	 * @param string $channel The channel name (without redis: prefix).
	 * @param mixed $payload The data to publish.
	 * @return int The number of clients that received the message.
	 */
	public function publish(string $channel, mixed $payload): int
	{
		$message = is_string($payload) ? $payload : json_encode($payload);
		return $this->redis->publish($channel, $message);
	}

	/**
	 * Subscribes to a Redis channel with a callback.
	 *
	 * @param string $channel The channel name (without redis: prefix).
	 * @param callable $callback The callback to execute when a message is received.
	 * @return string A unique subscription token.
	 */
	public function subscribe(string $channel, callable $callback): string
	{
		$token = $this->generateToken();

		if (!isset($this->subscriptions[$channel]))
		{
			$this->subscriptions[$channel] = [];
		}

		$this->subscriptions[$channel][$token] = $callback;

		return $token;
	}

	/**
	 * Unsubscribes from a Redis channel using a subscription token.
	 *
	 * @param string $channel The channel name.
	 * @param string $token The subscription token.
	 * @return void
	 */
	public function unsubscribe(string $channel, string $token): void
	{
		if (isset($this->subscriptions[$channel][$token]))
		{
			unset($this->subscriptions[$channel][$token]);

			// Clean up empty channel subscriptions
			if (empty($this->subscriptions[$channel]))
			{
				unset($this->subscriptions[$channel]);
			}
		}
	}

	/**
	 * Starts listening to subscribed channels.
	 * This is a blocking operation and should be run in a separate process or async context.
	 *
	 * @return void
	 */
	public function startListening(): void
	{
		if (empty($this->subscriptions) || $this->listening)
		{
			return;
		}

		$this->listening = true;
		$channels = array_keys($this->subscriptions);

		$this->redis->subscribe($channels, function ($channel, $message) {
			$this->handleMessage($channel, $message);
		});
	}

	/**
	 * Handles an incoming message from Redis.
	 *
	 * @param string $channel The channel the message was received on.
	 * @param string $message The message payload.
	 * @return void
	 */
	protected function handleMessage(string $channel, string $message): void
	{
		if (!isset($this->subscriptions[$channel]))
		{
			return;
		}

		// Attempt to decode JSON, otherwise pass raw message
		$payload = json_decode($message, true) ?? $message;

		foreach ($this->subscriptions[$channel] as $callback)
		{
			$callback($payload);
		}
	}

	/**
	 * Stops listening to Redis channels.
	 *
	 * @return void
	 */
	public function stopListening(): void
	{
		if (!$this->listening)
		{
			return;
		}

		$this->listening = false;
		$this->redis->unsubscribe();
	}

	/**
	 * Checks if the adapter is currently listening.
	 *
	 * @return bool
	 */
	public function isListening(): bool
	{
		return $this->listening;
	}

	/**
	 * Gets all active subscriptions.
	 *
	 * @return array
	 */
	public function getSubscriptions(): array
	{
		return $this->subscriptions;
	}

	/**
	 * Gets the Redis driver instance.
	 *
	 * @return RedisDriver
	 */
	public function getRedis(): RedisDriver
	{
		return $this->redis;
	}

	/**
	 * Generates a unique subscription token.
	 *
	 * @return string
	 */
	protected function generateToken(): string
	{
		return 'redis-' . uniqid('', true);
	}
}
