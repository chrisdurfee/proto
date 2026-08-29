<?php declare(strict_types=1);
namespace Proto\Cache;

use Proto\Patterns\Creational\Singleton;
use Proto\Cache\Drivers\Driver;

/**
 * Cache
 *
 * Handles caching using a configurable driver.
 *
 * @package Proto\Cache
 */
class Cache extends Singleton
{
	/**
	 * The cache driver instance.
	 *
	 * @var Driver|null
	 */
	protected ?Driver $driver = null;

	/**
	 * The environment setting.
	 *
	 * @var string|null
	 */
	protected static ?string $env = null;

	/**
	 * Initializes the cache driver.
	 */
	protected function __construct()
	{
		$this->loadDriver();
	}

	/**
	 * Retrieves the cache driver class name.
	 *
	 * @return string|null The driver class name or null if not configured.
	 */
	protected function getDriverClassName(): ?string
	{
		$cache = env('cache');
		$driver = $cache->driver ?? null;

		return !empty($driver) ? __NAMESPACE__ . '\\Drivers\\' . $driver : null;
	}

	/**
	 * Loads the cache driver.
	 *
	 * Defense in depth: well-behaved drivers (e.g. RedisDriver) already
	 * catch their own connection failures and never throw. This catch
	 * guards against a misbehaving/future driver doing otherwise — a cache
	 * backend being unreachable must never take down the whole app, since
	 * every cache call already degrades to "no cache" when $driver is null.
	 *
	 * @return void
	 */
	protected function loadDriver(): void
	{
		$class = $this->getDriverClassName();
		if ($class === null || !class_exists($class))
		{
			return;
		}

		try
		{
			$this->driver = new $class();
		}
		catch (\Throwable)
		{
			$this->driver = null;
		}
	}

	/**
	 * Retrieves the cache driver instance.
	 *
	 * @return Driver|null
	 */
	public function getDriver(): ?Driver
	{
		return $this->driver;
	}

	/**
	 * Retrieves the singleton driver instance.
	 *
	 * @return Driver|null
	 */
	public static function driver(): ?Driver
	{
		return static::getInstance()->getDriver();
	}

	/**
	 * Retrieves the last error from the driver.
	 *
	 * @return \Exception|null
	 */
	public static function getLastError(): ?\Exception
	{
		$driver = static::driver();
		return $driver ? $driver->getLastError() : null;
	}

	/**
	 * Retrieves a value from the cache.
	 *
	 * @param string $key The cache key.
	 * @return string|null The cached value or null if not found.
	 */
	public static function get(string $key): ?string
	{
		$driver = static::driver();
		return $driver ? $driver->get($key) : null;
	}

	/**
	 * Retrieves all cache keys matching a pattern.
	 *
	 * @param string $key The pattern to search for.
	 * @return array|null The matching keys or null if none found.
	 */
	public static function keys(string $key): ?array
	{
		$driver = static::driver();
		return $driver ? $driver->keys($key) : null;
	}

	/**
	 * Increments a cache value.
	 *
	 * @param string $key The cache key.
	 * @return int The new incremented value or 0 if driver is unavailable.
	 */
	public static function incr(string $key): int
	{
		$driver = static::driver();
		return $driver ? $driver->incr($key) : 0;
	}

	/**
	 * Sets a key's time-to-live in seconds without changing its value.
	 *
	 * @param string $key The cache key.
	 * @param int $seconds TTL in seconds.
	 * @return bool True when expiry was applied.
	 */
	public static function expire(string $key, int $seconds): bool
	{
		$driver = static::driver();
		return $driver ? $driver->expire($key, $seconds) : false;
	}

	/**
	 * Returns remaining TTL in seconds (-2 missing, -1 no expiry, >=0 left).
	 *
	 * @param string $key The cache key.
	 * @return int
	 */
	public static function ttl(string $key): int
	{
		$driver = static::driver();
		return $driver ? $driver->ttl($key) : -2;
	}

	/**
	 * Retrieves the application environment.
	 *
	 * @return string The environment name.
	 */
	protected static function getEnv(): string
	{
		return static::$env ??= env('env');
	}

	/**
	 * Whether caching is enabled for this environment.
	 *
	 * Defaults to off in `dev` so a developer sees every change immediately
	 * without reasoning about stale keys. That default has a cost: the cache
	 * path is then never exercised locally, so a keying or invalidation bug
	 * only surfaces in an environment where it is expensive to debug. Set
	 * `cache.enabled` to override in either direction and exercise the real
	 * path locally.
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		$cache = env('cache');
		if (is_object($cache) && isset($cache->enabled))
		{
			return (bool)$cache->enabled;
		}

		return static::getEnv() !== 'dev';
	}

	/**
	 * Checks if caching is supported.
	 *
	 * @return bool True if caching is enabled and supported.
	 */
	public static function isSupported(): bool
	{
		if (!static::isEnabled())
		{
			return false;
		}

		$driver = static::driver();
		return $driver && $driver->isSupported();
	}

	/**
	 * Checks if a key exists in the cache.
	 *
	 * @param string $key The cache key.
	 * @return bool True if the key exists, otherwise false.
	 */
	public static function has(string $key): bool
	{
		$driver = static::driver();
		return $driver ? $driver->has($key) : false;
	}

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $key The cache key.
	 * @return bool True if the key was deleted, otherwise false.
	 */
	public static function delete(string $key): bool
	{
		$driver = static::driver();
		return $driver ? $driver->delete($key) : false;
	}

	/**
	 * Stores a value in the cache.
	 *
	 * @param string $key The cache key.
	 * @param string $value The value to store.
	 * @param int|null $expire Expiration time in seconds (optional).
	 * @return void
	 */
	public static function set(string $key, string $value, ?int $expire = null): void
	{
		$driver = static::driver();
		if ($driver)
        {
			$driver->set($key, $value, $expire);
		}
	}

	/**
	 * Retrieves many keys in one call.
	 *
	 * @param array<int, string> $keys
	 * @return array<string, string|null> Keyed by cache key; null where absent.
	 */
	public static function getMultiple(array $keys): array
	{
		$driver = static::driver();
		return $driver ? $driver->getMultiple($keys) : array_fill_keys($keys, null);
	}

	/**
	 * Deletes many keys in one call.
	 *
	 * @param array<int, string> $keys
	 * @return int Number of keys removed.
	 */
	public static function deleteMultiple(array $keys): int
	{
		$driver = static::driver();
		return $driver ? $driver->deleteMultiple($keys) : 0;
	}

	/**
	 * Stores a value only when the key does not already exist.
	 *
	 * @param string $key The cache key.
	 * @param string $value The value to store.
	 * @param int|null $expire Expiration time in seconds.
	 * @return bool True when this caller created the key.
	 */
	public static function add(string $key, string $value, ?int $expire = null): bool
	{
		$driver = static::driver();
		return $driver ? $driver->add($key, $value, $expire) : false;
	}
}