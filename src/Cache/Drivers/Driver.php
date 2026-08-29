<?php declare(strict_types=1);
namespace Proto\Cache\Drivers;

/**
 * Abstract Driver Class
 *
 * The base class for all cache drivers.
 *
 * @package Proto\Cache\Drivers
 * @abstract
 */
abstract class Driver
{
	/**
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * This will set an error.
     *
     * @param \Exception $error
     * @return void
     */
    protected function setError(\Exception $error): void
    {
        array_push($this->errors, $error);
    }

    /**
     * This will get the last error.
     *
     * @return \Exception|null
     */
    public function getLastError(): ?\Exception
    {
        return $this->errors[count($this->errors) - 1] ?? null;
    }

	/**
	 * Checks if the cache system is supported.
	 *
	 * @return bool
	 */
	abstract public function isSupported(): bool;

	/**
	 * Retrieves a value from the cache.
	 *
	 * @param string $key
	 * @return string|null The cached value or null if not found.
	 */
	abstract public function get(string $key): ?string;

	/**
	 * Checks if an item exists in the cache.
	 *
	 * @param string $key
	 * @return bool True if the key exists, otherwise false.
	 */
	abstract public function has(string $key): bool;

	/**
	 * Increments a numeric value in the cache.
	 *
	 * @param string $key
	 * @return int The new incremented value.
	 */
	abstract public function incr(string $key): int;

	/**
	 * Sets a key's time-to-live in seconds without changing its value.
	 *
	 * Default no-op for drivers that lack native expiry control.
	 *
	 * @param string $key
	 * @param int $seconds
	 * @return bool True when expiry was applied.
	 */
	public function expire(string $key, int $seconds): bool
	{
		return false;
	}

	/**
	 * Returns remaining TTL in seconds.
	 *
	 * Convention matches Redis: -2 missing, -1 no expiry, >=0 seconds left.
	 *
	 * @param string $key
	 * @return int
	 */
	public function ttl(string $key): int
	{
		return -2;
	}

	/**
	 * Retrieves a list of keys matching a pattern.
	 *
	 * @param string $pattern Pattern to match keys.
	 * @return array|null Array of matching keys or null if none found.
	 */
	abstract public function keys(string $pattern): ?array;

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $key
	 * @return bool True on success, false on failure.
	 */
	abstract public function delete(string $key): bool;

	/**
	 * Sets a value in the cache.
	 *
	 * @param string $key The cache key.
	 * @param string $value The value to store.
	 * @param int|null $expire Optional expiration time in seconds.
	 * @return void
	 */
	abstract public function set(string $key, string $value, ?int $expire = null): void;

	/**
	 * Clears all cache items.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract public function clear(): bool;

	/**
	 * Retrieves many keys at once.
	 *
	 * Drivers that support a native multi-get should override this: the
	 * default costs one round trip per key, which is the whole reason for
	 * having the method. Missing keys are returned as null so the caller can
	 * tell "absent" from "cached empty string" without a second lookup.
	 *
	 * @param array<int, string> $keys
	 * @return array<string, string|null> Keyed by cache key, in the order requested.
	 */
	public function getMultiple(array $keys): array
	{
		$values = [];
		foreach ($keys as $key)
		{
			$values[$key] = $this->get($key);
		}

		return $values;
	}

	/**
	 * Deletes many keys at once.
	 *
	 * @param array<int, string> $keys
	 * @return int Number of keys removed.
	 */
	public function deleteMultiple(array $keys): int
	{
		$removed = 0;
		foreach ($keys as $key)
		{
			if ($this->delete($key))
			{
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Sets a value only when the key does not already exist.
	 *
	 * This is the primitive a stampede lock is built on, so the winner must
	 * be decided by the cache server rather than by the caller. The default
	 * implementation is check-then-set and therefore racy: two callers can
	 * both see the key missing and both believe they won. Any driver that
	 * can do this atomically must override it.
	 *
	 * @param string $key
	 * @param string $value
	 * @param int|null $expire Expiration in seconds.
	 * @return bool True when this caller created the key.
	 */
	public function add(string $key, string $value, ?int $expire = null): bool
	{
		if ($this->has($key))
		{
			return false;
		}

		$this->set($key, $value, $expire);
		return true;
	}
}