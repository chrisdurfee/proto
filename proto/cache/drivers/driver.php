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
}