<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Cache\Cache;
use Proto\Http\Router\Response;

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
	 * Cache class reference.
	 *
	 * @var string|null
	 */
	protected static ?string $cache = null;

	/**
	 * Initializes the cache class reference.
	 *
	 * @param string $cache
	 * @return void
	 */
	protected static function setupCache(
		string $cache = Cache::class
	): void
	{
		if (!$cache::isSupported())
		{
			return;
		}

		self::$cache = $cache;
	}

	/**
	 * Retrieves the cache class reference.
	 *
	 * @return string|null
	 */
	protected static function cache(): ?string
	{
		if (self::$cache === null)
		{
			static::setupCache();
		}

		return self::$cache ?? null;
	}

	/**
	 * Checks if the key is cached.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected static function isCached(string $key): bool
	{
		$cache = self::cache();
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
		$cache = self::cache();
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
		$cache = self::cache();
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
		$cache = static::cache();
		if ($cache === null)
		{
			return;
		}

		$id = 'rate-limit:' . $limit->id();
		if (!static::isCached($id))
		{
			static::set($id, $limit->getTimeLimit());
			return;
		}

		$requests = static::increment($id);
		if ($limit->isOverLimit($requests))
		{
			static::sendRateLimitResponse($limit, $requests);
		}
	}

	/**
	 * Sets the rate limit headers.
	 *
	 * @param Limit $limit
	 * @param int $requests
	 * @return void
	 */
	private static function setRateHeaders(Limit $limit, int $requests): void
	{
		$maxRequests = $limit->getRequestLimit();
		header('Retry-After: ' . $limit->getTimeLimit());
		header('X-RateLimit-Limit: ' . $maxRequests);
		header('X-RateLimit-Remaining: ' . max(0, $maxRequests - $requests));
	}

	/**
	 * Sends a rate limit exceeded response.
	 *
	 * @param Limit $limit
	 * @param int $requests
	 * @return void
	 */
	protected static function sendRateLimitResponse(Limit $limit, int $requests): void
	{
		self::setRateHeaders($limit, $requests);

		$responseCode = 429;
		$data = (object)[
			'message' => 'Too Many Requests',
			'success' => false
		];

		$response = new Response();
		$response->json($data, $responseCode);

		exit;
	}
}