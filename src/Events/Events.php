<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Patterns\Creational\Singleton;
use Proto\Patterns\Structural\PubSub;
use Proto\Cache\Cache;
use Proto\Cache\Drivers\RedisDriver;

/**
 * Class Events
 *
 * Provides a Singleton-based event system for subscribing, emitting, and removing events.
 * Supports both local (PubSub) and distributed (Redis) event handling via prefix-based routing.
 *
 * Events with the "redis:" prefix are automatically routed to Redis pub/sub,
 * enabling distributed event-driven architectures across multiple application instances.
 *
 * @package Proto\Events
 */
class Events extends Singleton
{
	/**
	 * @var self|null The singleton instance.
	 */
	protected static ?self $instance = null;

	/**
	 * @var string The prefix used to identify Redis-based events.
	 */
	protected const REDIS_PREFIX = 'redis:';

	/**
	 * @var RedisPubSubAdapter|null The Redis pub/sub adapter.
	 */
	protected ?RedisPubSubAdapter $redisAdapter = null;

	/**
	 * Initializes the PubSub instance.
	 *
	 * @param PubSub $pubSub The PubSub instance to use.
	 */
	protected function __construct(
		protected PubSub $pubSub = new PubSub()
	)
	{
		$this->initializeRedis();
	}

	/**
	 * Initializes the Redis adapter if Redis cache is configured.
	 *
	 * @return void
	 */
	protected function initializeRedis(): void
	{
		try
		{
			$cacheDriver = env('cache')?->driver ?? null;

			if ($cacheDriver === 'redis')
			{
				$cache = Cache::getInstance();
				$driver = $cache->getDriver();

				if ($driver instanceof RedisDriver)
				{
					$this->redisAdapter = new RedisPubSubAdapter($driver);
				}
			}
		}
		catch (\Exception $e)
		{
			// Redis not available or not configured - continue with local events only
		}
	}

	/**
	 * Checks if a key should use Redis pub/sub.
	 *
	 * @param string $key The event key.
	 * @return bool
	 */
	protected function isRedisEvent(string $key): bool
	{
		return str_starts_with($key, self::REDIS_PREFIX);
	}

	/**
	 * Strips the Redis prefix from an event key.
	 *
	 * @param string $key The event key.
	 * @return string The key without the prefix.
	 */
	protected function stripRedisPrefix(string $key): string
	{
		return substr($key, strlen(self::REDIS_PREFIX));
	}

	/**
	 * Gets the Redis adapter instance.
	 *
	 * @return RedisPubSubAdapter|null
	 */
	public function getRedisAdapter(): ?RedisPubSubAdapter
	{
		return $this->redisAdapter;
	}

	/**
	 * Publishes an event.
	 *
	 * @param string $key The event identifier.
	 * @param mixed $payload The event data.
	 */
	public function emit(string $key, mixed $payload): void
	{
		if ($this->isRedisEvent($key) && $this->redisAdapter !== null)
		{
			$channel = $this->stripRedisPrefix($key);
			$this->redisAdapter->publish($channel, $payload);
			return;
		}

		$this->pubSub->publish($key, $payload);
	}

	/**
	 * Subscribes to an event.
	 *
	 * @param string $key The event identifier.
	 * @param callable $callback The function to execute when the event is triggered.
	 * @return string|null The subscription token or null if failed.
	 */
	public function subscribe(string $key, callable $callback): ?string
	{
		if ($this->isRedisEvent($key) && $this->redisAdapter !== null)
		{
			$channel = $this->stripRedisPrefix($key);
			return $this->redisAdapter->subscribe($channel, $callback);
		}

		return $this->pubSub->subscribe($key, $callback);
	}

	/**
	 * Unsubscribes from an event.
	 *
	 * @param string $key The event identifier.
	 * @param string $token The subscription token to remove.
	 */
	public function unsubscribe(string $key, string $token): void
	{
		if ($this->isRedisEvent($key) && $this->redisAdapter !== null)
		{
			$channel = $this->stripRedisPrefix($key);
			$this->redisAdapter->unsubscribe($channel, $token);
			return;
		}

		$this->pubSub->unsubscribe($key, $token);
	}

	/**
	 * Publishes an event (static wrapper for `emit()`).
	 *
	 * @param string $key The event identifier.
	 * @param mixed $payload The event data.
	 */
	public static function update(string $key, mixed $payload): void
	{
		static::getInstance()->emit($key, $payload);
	}

	/**
	 * Subscribes to an event (static wrapper for `subscribe()`).
	 *
	 * @param string $key The event identifier.
	 * @param callable $callback The function to execute when the event is triggered.
	 * @return string|null The subscription token or null if failed.
	 */
	public static function on(string $key, callable $callback): ?string
	{
		return static::getInstance()->subscribe($key, $callback);
	}

	/**
	 * Unsubscribes from an event (static wrapper for `unsubscribe()`).
	 *
	 * @param string $key The event identifier.
	 * @param string $token The subscription token to remove.
	 */
	public static function off(string $key, string $token): void
	{
		static::getInstance()->unsubscribe($key, $token);
	}
}