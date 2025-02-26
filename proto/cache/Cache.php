<?php declare(strict_types=1);
namespace Proto\Cache;

use Proto\Patterns\Creational\Singleton;
use Proto\Config;
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
	 * The singleton instance.
	 *
	 * @var self|null
	 */
	protected static ?self $instance = null;

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
		$cache = Config::access('cache');
		$driver = $cache->driver ?? null;

		return !empty($driver) ? __NAMESPACE__ . '\\Drivers\\' . $driver : null;
	}

	/**
	 * Loads the cache driver.
	 *
	 * @return void
	 */
	protected function loadDriver(): void
	{
		$class = $this->getDriverClassName();
		if ($class !== null && class_exists($class))
        {
			$this->driver = new $class();
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
	 * Retrieves the application environment.
	 *
	 * @return string The environment name.
	 */
	protected static function getEnv(): string
	{
		return static::$env ??= Config::access('env');
	}

	/**
	 * Checks if caching is supported.
	 *
	 * @return bool True if caching is enabled and supported.
	 */
	public static function isSupported(): bool
	{
		if (static::getEnv() === 'dev')
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
}