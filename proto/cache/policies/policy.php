<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

use Proto\Cache\Cache;
use Proto\Utils\Format\JsonFormat;

/**
 * Policy
 *
 * This will be the base class for all cache policies.
 *
 * @package Proto\Cache\Policies
 */
abstract class Policy
{
    /**
	 * @var object $controller
	 */
    protected object $controller;

    /**
     * @var int $expire in seconds
     */
    protected $expire = 300;

    /**
     *  This will create a cache policy.
     *
     * @param object $controller
     * @return void
     */
    public function __construct(object $controller)
    {
        $this->controller = $controller;
    }

    /**
     * This will get a value from the cache.
     *
     * @param string $key
     * @return mixed
     */
	public function getValue(string $key): mixed
    {
        $value = Cache::get($key);
        return ($value)? JsonFormat::decode($value) : $value;
    }

    /**
     * This will get the keys from the cache.
     *
     * @param string $key
     * @return mixed
     */
	public function getKeys(string $key): mixed
    {
        return Cache::keys($key);
    }

    /**
     * This will check if the item is in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function hasKey(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * This will delete a value from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function deleteKey(string $key): bool
    {
        return Cache::delete($key);
    }

    /**
     * This will set a value to the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expire
     * @return void
     */
    public function setValue(string $key, mixed $value, ?int $expire = null): void
    {
        Cache::set($key, json_encode($value), $expire);
    }

    /**
     * This will create a cache key.
     *
     * @param string $method
     * @param mixed $params
     * @return string
     */
    protected function createKey(string $method, mixed $params): string
    {
        return $this->controller::class . ':' . $method . ':' . (string)$params;
    }
}