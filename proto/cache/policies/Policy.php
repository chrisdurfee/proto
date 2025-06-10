<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

use Proto\Cache\Cache;
use Proto\Utils\Format\JsonFormat;
use Proto\Controllers\Controller;

/**
 * Policy
 *
 * Base class for all cache policies.
 *
 * @package Proto\Cache\Policies
 */
abstract class Policy implements CachePolicyInterface
{
	/**
	 * Cache expiration time in seconds.
	 *
	 * @var int
	 */
	protected int $expire = 300;

	/**
	 * Creates a cache policy instance.
	 *
	 * @param Controller $controller The controller instance.
	 * @return void
	 */
	public function __construct(
		protected Controller $controller
	)
	{
	}

	/**
	 * Retrieves a value from the cache.
	 *
	 * @param string $key The cache key.
	 * @return mixed The decoded cache value, or null if not found.
	 */
	public function getValue(string $key): mixed
	{
		$value = Cache::get($key);
		return $value !== null ? JsonFormat::decode($value) : null;
	}

	/**
	 * Retrieves cache keys matching a pattern.
	 *
	 * @param string $key The key pattern.
	 * @return array|null The list of keys, or null if none found.
	 */
	public function getKeys(string $key): ?array
	{
		return Cache::keys($key);
	}

	/**
	 * Checks if a cache key exists.
	 *
	 * @param string $key The cache key.
	 * @return bool True if the key exists, otherwise false.
	 */
	public function hasKey(string $key): bool
	{
		return Cache::has($key);
	}

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $key The cache key.
	 * @return bool True if the key was deleted, otherwise false.
	 */
	public function deleteKey(string $key): bool
	{
		return Cache::delete($key);
	}

	/**
	 * Stores a value in the cache.
	 *
	 * @param string $key The cache key.
	 * @param mixed $value The value to store.
	 * @param int|null $expire Expiration time in seconds (optional).
	 * @return void
	 */
	public function setValue(string $key, mixed $value, ?int $expire = null): void
	{
		Cache::set($key, JsonFormat::encode($value), $expire ?? $this->expire);
	}

	/**
	 * Creates a unique cache key.
	 *
	 * @param string $method The method name.
	 * @param mixed $params The method parameters.
	 * @return string The generated cache key.
	 */
	protected function createKey(string $method, mixed $params): string
	{
		return $this->controller::class . ':' . $method . ':' . (string)$params;
	}
}
