<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Cache\Cache;
use Proto\Http\Router\Response;
use Proto\Utils\Format\JsonFormat;

/**
 * Class RateLimiter
 * Provides rate limiting functionality.
 *
 * @package Proto\Http
 */
class RateLimiter
{
	/**
	 * This will store all the limits.
	 *
	 * @var array $limits
	 */
	protected static array $limits = [];

	/**
	 * Cache class reference.
	 *
	 * @var string $cache
	 */
	protected static string $cache;

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
		$cache = self::$cache;
		return $cache::isSupported();
	}

	/**
	 * Retrieves the cache class reference.
	 *
	 * @return string|null
	 */
	protected static function getCache(): ?string
	{
		if (!isset(self::$cache))
		{
			static::setupCache();
		}

		if (!static::isCacheSupported())
		{
			return null;
		}

		return self::$cache;
	}

	/**
	 * Checks if the string is cached.
	 *
	 * @param string $string
	 * @return bool
	 */
	protected static function isCached(string $string): bool
	{
		$cache = self::$cache;
		return $cache::has($string);
	}

	/**
	 * Sets the string value to 1 in the cache.
	 *
	 * @param string $string
	 * @param int $expiration
	 * @return void
	 */
	protected static function set(string $string, int $expiration): void
	{
		$cache = self::$cache;
		$cache::set($string, (string)1, $expiration);
	}

	/**
	 * Increments the string value in the cache.
	 *
	 * @param string $string
	 * @return int
	 */
	protected static function incr(string $string): int
	{
		$cache = self::$cache;
		return $cache::incr($string);
	}

	/**
	 * Checks the rate limit.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	public static function check(Limit $limit): void
	{
		$cache = static::getCache();
		if (!isset($cache))
		{
			return;
		}

		$id = $limit->id();
		if (!static::isCached($id))
		{
			static::set($id, $limit->getTimeLimit());
			return;
		}

		$requests = static::incr($id);
		if ($limit->isOverLimit($requests))
		{
			static::response();
		}
	}

	/**
	 * Sends rate limit exceeded response.
	 *
	 * @return void
	 */
	protected static function response(): void
	{
		$RESPONSE_CODE = 429;
		$response = new Response();
		$response->render($RESPONSE_CODE);

		JsonFormat::encodeAndRender((object)[
			'message' => 'Too Many Requests',
			'success' => false
		]);
		die;
	}
}