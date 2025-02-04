<?php declare(strict_types=1);
namespace Proto\Cache\Drivers;

use \Redis;

/**
 * RedisDriver
 *
 * This class is the driver for the Redis cache.
 *
 * @package Proto\Cache\Drivers
 */
class RedisDriver extends Driver
{
	/**
	 * @var object $db
	 */
	protected object $db;

	/**
	 * Constructor method that connects to the Redis database.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->connect();
	}

	/**
	 * Checks if the Redis cache is supported and available.
	 *
	 * @return bool
	 */
	public function isSupported(): bool
	{
		return class_exists('Redis');
	}

	/**
	 * Retrieves the cache settings.
	 *
	 * @return object
	 */
	protected function getCacheSettings(): object
	{
		$cache = env('cache');
		return $cache->connection;
	}

	/**
	 * Connects to the Redis database.
	 *
	 * @SuppressWarnings PHP0413
	 * @return void
	 */
	protected function connect(): void
	{
		if ($this->isSupported() === false)
		{
			return;
		}

		$connection = $this->getCacheSettings();
		$this->db = new Redis();

		/**
		 * We use pconnect instead of connect
		 * to reduce the number of connections.
		 */
		$this->db->pconnect($connection->host, $connection->port);
		// $this->db->auth($connection->password);
	}

	/**
	 * Retrieves a value from the cache by its key.
	 *
	 * @param string $key
	 * @return string|null
	 */
	public function get(string $key): ?string
	{
		return $this->db->get($key);
	}

	/**
	 * Retrieves all keys by a given key pattern.
	 *
	 * @param string $key
	 * @return array|null
	 */
	public function keys(string $key): ?array
	{
		return $this->db->keys($key);
	}

	/**
	 * Checks if an item exists in the cache.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		return (bool)$this->db->exists($key);
	}

	/**
	 * Increments an item in the cache.
	 *
	 * @param string $key
	 * @return int
	 */
	public function incr(string $key): int
	{
		return $this->db->incr($key);
	}

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete(string $key): bool
	{
		return (bool)$this->db->del($key);
	}

	/**
	 * Sets a value in the cache with an optional expiration time.
	 *
	 * @param string $key
	 * @param string $value
	 * @param int|null $expire
	 * @return void
	 */
	public function set(string $key, string $value, ?int $expire = null): void
	{
		$this->db->set($key, $value, $expire);
	}
}