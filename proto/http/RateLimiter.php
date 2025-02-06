<?php declare(strict_types=1);

namespace Proto\Http;

use Proto\Cache\Cache;
use Proto\Http\Router\Response;
use Proto\Utils\Format\JsonFormat;

/**
 * Class RateLimiter
 *
 * Provides rate limiting functionality.
 *
 * @package Proto\Http
 */
class RateLimiter
{
	/**
	 * Stores all the limits.
	 *
	 * @var array
	 */
	protected static array $limits = [];

	/**
	 * Cache class reference.
	 *
	 * @var string|null
	 */
	protected static ?string $cache = null;

	/**
	 * Initializes the cache class reference.
	 *
	 * @return void
	 */
	protected static function setupCache(): void
	{
		self::$cache = Cache::class;
	}

	/**
	 * Checks if cache is supported.
	 *
	 * @return bool
	 */
	protected static function isCacheSupported(): bool
	{
		$cache = self::$cache ?? static::getCache();
		return isset($cache) && $cache::isSupported();
	}

	/**
	 * Retrieves the cache class reference.
	 *
	 * @return string|null
	 */
	protected static function getCache(): ?string
	{
		if (self::$cache === null)
		{
			static::setupCache();
		}

		return static::isCacheSupported() ? self::$cache : null;
	}

	/**
	 * Checks if the key is cached.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected static function isCached(string $key): bool
	{
		$cache = self::getCache();
		return isset($cache) && $cache::has($key);
	}

	/**
	 * Sets a key-value pair in the cache with expiration.
	 *
	 * @param string $key
	 * @param int $expiration
	 * @return void
	 */
	protected static function set(string $key, int $expiration): void
	{
		$cache = self::getCache();
		if ($cache)
		{
			$cache::set($key, '1', $expiration);
		}
	}

	/**
	 * Increments the value of a cached key.
	 *
	 * @param string $key
	 * @return int
	 */
	protected static function increment(string $key): int
	{
		$cache = self::getCache();
		return isset($cache) ? $cache::incr($key) : 1;
	}

	/**
	 * Checks if the rate limit is exceeded.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	public static function check(Limit $limit): void
	{
		$cache = static::getCache();
		if ($cache === null)
		{
			return;
		}

		$id = $limit->id();
		if (!static::isCached($id))
		{
			static::set($id, $limit->getTimeLimit());
			return;
		}

		$requests = static::increment($id);
		if ($limit->isOverLimit($requests))
		{
			static::sendRateLimitResponse();
		}
	}

	/**
	 * Sends a rate limit exceeded response.
	 *
	 * @return void
	 */
	protected static function sendRateLimitResponse(): void
	{
		$responseCode = 429;
		$response = new Response();
		$response->render($responseCode);

		JsonFormat::encodeAndRender([
			'message' => 'Too Many Requests',
			'success' => false
		]);

		exit;
	}
}