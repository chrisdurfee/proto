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
	 * Redis database connection instance. Nullable: a failed/unavailable
	 * connection must never crash the request (@see connect()), so every
	 * accessor below checks $connected rather than assuming $db is usable.
	 *
	 * @SuppressWarnings PHP0413
	 * @var Redis|null
	 */
	protected ?Redis $db = null;

	/**
	 * Whether the connection was established successfully. Distinct from
	 * isSupported() (extension presence): this reflects actual reachability.
	 *
	 * @var bool
	 */
	protected bool $connected = false;

	/**
	 * Constructor method that initializes the Redis connection.
	 *
	 * Never throws: a Redis outage must degrade the app to "no cache" (falls
	 * through to the database) rather than fatal every cacheable request.
	 * connect() catches its own failures; isSupported()/every accessor below
	 * checks $connected before touching $db.
	 */
	public function __construct()
	{
		$this->connect();
	}

	/**
	 * Checks if Redis is available: the extension is loaded AND a live
	 * connection was established. Callers (Cache::isSupported(), etc.) rely
	 * on this to decide whether to bother hitting the cache at all.
	 *
	 * @return bool
	 */
	public function isSupported(): bool
	{
		/**
		 * @SuppressWarnings PHP0413
		 */
		return class_exists(Redis::class) && $this->connected;
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
	 * Any failure (unreachable host, wrong port, bad auth, misconfiguration)
	 * is caught and recorded via setError() instead of thrown, so a Redis
	 * outage degrades the app to running without a cache rather than
	 * fataling every request that touches a cacheable controller.
	 *
	 * @return void
	 */
	protected function connect(): void
	{
		if (!class_exists(Redis::class))
		{
			return;
		}

		try
		{
			$connection = $this->getCacheSettings();
			$this->db = new Redis();

			// Use a short connect timeout so an unreachable host fails fast
			// instead of stalling the request for the OS-level TCP timeout.
			if (!$this->db->pconnect($connection->host, $connection->port, 1.0))
			{
				throw new \RuntimeException('Failed to connect to Redis server.');
			}

			// Authenticate if a password is set
			if (!empty($connection->password) && !$this->db->auth($connection->password))
			{
				throw new \RuntimeException('Redis authentication failed.');
			}

			$this->connected = true;
		}
		catch (\Throwable $e)
		{
			$this->connected = false;
			$this->db = null;
			$this->setError(
				$e instanceof \Exception ? $e : new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e)
			);
		}
	}

	/**
	 * Runs a Redis operation, swallowing connection-level failures so a
	 * mid-request drop (stale persistent connection, network blip, Redis
	 * restart) degrades to the operation's fallback value instead of
	 * throwing out of a cache accessor and breaking the request.
	 *
	 * @param callable $operation Receives the connected Redis instance.
	 * @param mixed $fallback Value to return when unsupported or on failure.
	 * @return mixed
	 */
	protected function attempt(callable $operation, mixed $fallback): mixed
	{
		if (!$this->connected || $this->db === null)
		{
			return $fallback;
		}

		try
		{
			return $operation($this->db);
		}
		catch (\Throwable $e)
		{
			$this->setError(
				$e instanceof \Exception ? $e : new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e)
			);
			return $fallback;
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
		return $this->attempt(function (Redis $db) use ($key): ?string
		{
			$value = $db->get($key);
			return $value !== false ? $value : null;
		}, null);
	}

	/**
	 * Retrieves keys matching a pattern using SCAN for better performance.
	 *
	 * @param string $pattern Key pattern.
	 * @return array Retrieved keys.
	 */
	public function keys(string $pattern): array
	{
		return $this->attempt(function (Redis $db) use ($pattern): array
		{
			return $this->collectScanKeys($db, $pattern);
		}, []);
	}

	/**
	 * Drain a Redis SCAN cursor, including empty pages while the cursor is open.
	 *
	 * `while ($chunk = $db->scan(...))` stops on `[]` even when the
	 * cursor is not yet 0 and silently drops the rest of the keyspace.
	 *
	 * @param object $db Redis connection (or a scan-compatible fake).
	 * @param string $pattern Key pattern.
	 * @return array
	 */
	protected function collectScanKeys(object $db, string $pattern): array
	{
		$iterator = null;
		$keys = [];

		do
		{
			$chunk = $db->scan($iterator, $pattern) ?: [];
			if ($chunk !== [])
			{
				$keys = array_merge($keys, $chunk);
			}
		}
		while ($iterator != 0);

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
		return $this->attempt(fn (Redis $db): bool => $db->exists($key) > 0, false);
	}

	/**
	 * Increments a numeric cache value.
	 *
	 * @param string $key Cache key.
	 * @return int The new incremented value.
	 */
	public function incr(string $key): int
	{
		return $this->attempt(fn (Redis $db): int => (int) $db->incr($key), 0);
	}

	/**
	 * Sets a key's time-to-live in seconds without changing its value.
	 *
	 * @param string $key Cache key.
	 * @param int $seconds TTL in seconds.
	 * @return bool True if expiry was applied.
	 */
	public function expire(string $key, int $seconds): bool
	{
		return $this->attempt(function (Redis $db) use ($key, $seconds): bool
		{
			$result = $db->expire($key, $seconds);
			return $result === true || $result === 1;
		}, false);
	}

	/**
	 * Returns remaining TTL in seconds (-2 missing, -1 no expiry).
	 *
	 * @param string $key Cache key.
	 * @return int
	 */
	public function ttl(string $key): int
	{
		return $this->attempt(fn (Redis $db): int => (int) $db->ttl($key), -2);
	}

	/**
	 * Deletes a key from the cache.
	 *
	 * @param string $key Cache key.
	 * @return bool True if key was deleted, otherwise false.
	 */
	public function delete(string $key): bool
	{
		return $this->attempt(fn (Redis $db): bool => $db->del($key) > 0, false);
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
		$this->attempt(function (Redis $db) use ($key, $value, $expire): bool
		{
			if ($expire !== null)
			{
				$db->setEx($key, $expire, $value);
			}
			else
			{
				$db->set($key, $value);
			}
			return true;
		}, false);
	}

	/**
	 * Clears all keys from the Redis cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear(): bool
	{
		return $this->attempt(fn (Redis $db): bool => $db->flushDB(), false);
	}

	/**
	 * Publishes a message to a Redis channel.
	 *
	 * @param string $channel The channel name.
	 * @param string $message The message to publish.
	 * @return int The number of clients that received the message.
	 */
	public function publish(string $channel, string $message): int
	{
		return $this->attempt(fn (Redis $db): int => (int) $db->publish($channel, $message), 0);
	}

	/**
	 * Subscribes to one or more Redis channels and executes a callback for each message.
	 *
	 * @param array|string $channels The channel(s) to subscribe to.
	 * @param callable $callback The callback function to execute for each message.
	 *                          Receives ($channel, $message) as parameters.
	 * @return void
	 */
	public function subscribe(array|string $channels, callable $callback): void
	{
		if (!$this->connected || $this->db === null)
		{
			return;
		}

		$channels = is_array($channels) ? $channels : [$channels];

		try
		{
			$this->db->subscribe($channels, function ($redis, $channel, $message) use ($callback) {
				$callback($channel, $message);
			});
		}
		catch (\Throwable $e)
		{
			$this->setError($e instanceof \Exception ? $e : new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e));
		}
	}

	/**
	 * Subscribes to Redis channels using pattern matching.
	 *
	 * @param array|string $patterns The pattern(s) to subscribe to.
	 * @param callable $callback The callback function to execute for each message.
	 *                          Receives ($pattern, $channel, $message) as parameters.
	 * @return void
	 */
	public function psubscribe(array|string $patterns, callable $callback): void
	{
		if (!$this->connected || $this->db === null)
		{
			return;
		}

		$patterns = is_array($patterns) ? $patterns : [$patterns];

		try
		{
			$this->db->psubscribe($patterns, function ($redis, $pattern, $channel, $message) use ($callback) {
				$callback($pattern, $channel, $message);
			});
		}
		catch (\Throwable $e)
		{
			$this->setError($e instanceof \Exception ? $e : new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e));
		}
	}

	/**
	 * Unsubscribes from Redis channels.
	 * Note: This must be called from within a subscribe callback.
	 *
	 * @param array|string|null $channels The channel(s) to unsubscribe from, or null for all.
	 * @return void
	 */
	public function unsubscribe(array|string|null $channels = null): void
	{
		if (!$this->connected || $this->db === null)
		{
			return;
		}

		try
		{
			if ($channels === null)
			{
				$this->db->unsubscribe();
			}
			else
			{
				$channels = is_array($channels) ? $channels : [$channels];
				$this->db->unsubscribe($channels);
			}
		}
		catch (\Throwable $e)
		{
			$this->setError($e instanceof \Exception ? $e : new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e));
		}
	}

	/**
	 * Appends one or more values to the tail of a Redis list.
	 *
	 * @param string $key List key.
	 * @param string ...$values Values to append.
	 * @return int The new length of the list, or 0 on failure.
	 */
	public function rPush(string $key, string ...$values): int
	{
		return $this->attempt(function (Redis $db) use ($key, $values): int
		{
			$result = $db->rPush($key, ...$values);
			return $result !== false ? (int) $result : 0;
		}, 0);
	}

	/**
	 * Removes and returns up to $count elements from the head of a
	 * Redis list (FIFO order).
	 *
	 * @param string $key List key.
	 * @param int $count Maximum number of elements to pop.
	 * @return array The popped elements, or an empty array if the list is empty.
	 */
	public function lPop(string $key, int $count = 1): array
	{
		return $this->attempt(function (Redis $db) use ($key, $count): array
		{
			$result = $db->lPop($key, max(1, $count));
			if ($result === false || $result === null)
			{
				return [];
			}

			return is_array($result) ? $result : [$result];
		}, []);
	}

	/**
	 * Gets the length of a Redis list.
	 *
	 * @param string $key List key.
	 * @return int The list length, or 0 if the key does not exist.
	 */
	public function lLen(string $key): int
	{
		return $this->attempt(function (Redis $db) use ($key): int
		{
			$result = $db->lLen($key);
			return $result !== false ? (int) $result : 0;
		}, 0);
	}

	/**
	 * Gets the underlying Redis connection instance.
	 *
	 * Returns null when the connection is unavailable — callers that need
	 * direct access (e.g. blocking list ops) must check for null themselves,
	 * same as every other accessor on this driver.
	 *
	 * @return Redis|null The Redis instance, or null if not connected.
	 */
	public function getConnection(): ?Redis
	{
		return $this->connected ? $this->db : null;
	}
}