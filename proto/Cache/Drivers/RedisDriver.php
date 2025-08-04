<?php declare(strict_types=1);
namespace Proto\Cache\Drivers;

use Redis;
use stdClass;

/**
 * RedisDriver
 *
 * This class serves as the driver for the Redis cache.
 *
 * @package Proto\Cache\Drivers
 */
class RedisDriver extends Driver
{
	/**
	 * Redis database connection instance.
	 *
	 * @SuppressWarnings PHP0413
	 * @var Redis
	 */
	protected Redis $db;

	/**
	 * Constructor method that initializes the Redis connection.
	 */
	public function __construct()
	{
		$this->connect();
	}

	/**
	 * Checks if Redis extension is available.
	 *
	 * @return bool
	 */
	public function isSupported(): bool
	{
		/**
		 * @SuppressWarnings PHP0413
		 */
		return class_exists(Redis::class);
	}

	/**
	 * Retrieves the Redis cache settings.
	 *
	 * @return stdClass Redis connection settings.
	 */
	protected function getCacheSettings(): stdClass
	{
		return env('cache')->connection;
	}

	/**
	 * Establishes a connection to the Redis server.
	 *
	 * @return void
	 */
	protected function connect(): void
	{
		if (!$this->isSupported())
		{
			return;
		}

		$connection = $this->getCacheSettings();
		$this->db = new Redis();

		// Use persistent connection to optimize performance
		if (!$this->db->pconnect($connection->host, $connection->port))
		{
			throw new \RuntimeException('Failed to connect to Redis server.');
		}

		// Authenticate if a password is set
		if (!empty($connection->password) && !$this->db->auth($connection->password))
		{
			throw new \RuntimeException('Redis authentication failed.');
		}
	}

	/**
	 * Retrieves a value from Redis by its key.
	 *
	 * @param string $key Cache key.
	 * @return string|null Cached value or null if not found.
	 */
	public function get(string $key): ?string
	{
		$value = $this->db->get($key);
		return $value !== false ? $value : null;
	}

	/**
	 * Retrieves keys matching a pattern using SCAN for better performance.
	 *
	 * @param string $pattern Key pattern.
	 * @return array Retrieved keys.
	 */
	public function keys(string $pattern): array
	{
		$iterator = null;
		$keys = [];

		while ($foundKeys = $this->db->scan($iterator, $pattern))
		{
			$keys = array_merge($keys, $foundKeys);
		}

		return $keys;
	}

	/**
	 * Checks if a cache key exists.
	 *
	 * @param string $key Cache key.
	 * @return bool True if key exists, otherwise false.
	 */
	public function has(string $key): bool
	{
		return $this->db->exists($key) > 0;
	}

	/**
	 * Increments a numeric cache value.
	 *
	 * @param string $key Cache key.
	 * @return int The new incremented value.
	 */
	public function incr(string $key): int
	{
		return (int) $this->db->incr($key);
	}

	/**
	 * Deletes a key from the cache.
	 *
	 * @param string $key Cache key.
	 * @return bool True if key was deleted, otherwise false.
	 */
	public function delete(string $key): bool
	{
		return $this->db->del($key) > 0;
	}

	/**
	 * Sets a value in Redis with an optional expiration time.
	 *
	 * @param string $key Cache key.
	 * @param string $value Cache value.
	 * @param int|null $expire Expiration time in seconds (optional).
	 * @return void
	 */
	public function set(string $key, string $value, ?int $expire = null): void
	{
		if ($expire !== null)
		{
			$this->db->setEx($key, $expire, $value);
		}
		else
		{
			$this->db->set($key, $value);
		}
	}

	/**
	 * Clears all keys from the Redis cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear(): bool
	{
		return $this->db->flushDB();
	}
}