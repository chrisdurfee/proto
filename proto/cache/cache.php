<?php declare(strict_types=1);
namespace Proto\Cache;

use Proto\Patterns\Creational\Singleton;
use Proto\Config;

/**
 * Cache
 *
 * This will handle the cache.
 *
 * @package Proto\Cache
 */
class Cache extends Singleton
{
    /**
     * @var Cache $instance
     */
    protected static $instance = null;

    /**
     * @var object|null $driver
     */
    protected ?object $driver = null;

    /**
     * @var string|null $env
     */
    protected static ?string $env = null;

    /**
     * This will load the cache driver.
     *
     * @return void
     */
    protected function __construct()
    {
		$this->LoadDriver();
	}

    /**
     * This will get the driver class.
     *
     * @return string|null
     */
    protected function getDriverClassName(): ?string
    {
        $cache = Config::access('cache');
        $driver = $cache->driver;
        if (empty($driver))
        {
            return null;
        }

        $class = __NAMESPACE__ . '\\Drivers\\' . $driver;
        return $class;
    }

    /**
     * This will load the cache driver.
     *
     * @return void
     */
    protected function loadDriver(): void
    {
        $class = $this->getDriverClassName();
        if ($class === null)
        {
            return;
        }

        $this->driver = new $class();
    }

    /**
     * This will get the driver.
     *
     * @return object|null
     */
    public function getDriver(): ?object
    {
        return $this->driver;
    }

    /**
     * This will get the cache driver.
     *
     * @return object|null
     */
    public static function driver(): ?object
    {
        $cache = static::getInstance();
        return $cache->getDriver();
    }

    /**
     * This will get the last error.
     *
     * @return \Exception|null
     */
	public static function getLastError(): ?\Exception
    {
        return static::driver()->getLastError();
    }

    /**
     * This will get a value from the cache.
     *
     * @param string $key
     * @return string|null
     */
	public static function get(string $key): ?string
    {
        return static::driver()->get($key);
    }

    /**
     * This will get all keys by key.
     *
     * @param string $key
     * @return array|null
     */
	public static function keys(string $key): ?array
    {
        return static::driver()->keys($key);
    }

    /**
     * This will increment a key.
     *
     * @param string $key
     * @return int
     */
	public static function incr(string $key): int
    {
        return static::driver()->incr($key);
    }

    /**
     * This will get the environment.
     *
     * @return string
     */
    protected static function getEnv(): string
    {
        return (static::$env ?? static::$env = Config::access('env'));
    }

    /**
     * This will get a value from the cache.
     *
     * @return bool
     */
	public static function isSupported(): bool
    {
        $env = static::getEnv();
        if ($env === 'dev')
        {
            return false;
        }

        $driver = static::driver();
        return ($driver && $driver->isSupported());
    }

    /**
     * This will check if the item is in the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return static::driver()->has($key);
    }

    /**
     * This will delete a value from the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        return static::driver()->delete($key);
    }

    /**
     * This will set a value to the cache.
     *
     * @param string $key
     * @param string $value
     * @param int|null $expire
     * @return void
     */
    public static function set(string $key, string $value, ?int $exipre = null): void
    {
        static::driver()->set($key, $value, $exipre);
    }
}