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
	 * Ensures the rate-limit key has a TTL.
	 *
	 * Redis INCR creates missing keys with no expiry. A race between
	 * EXISTS and key expiry can also leave immortal counters that
	 * permanently 429 an identity after maxRequests. Always (re)apply
	 * TTL when the key is new or has no expiry.
	 *
	 * @param string $key
	 * @param int $expiration
	 * @param int $requests
	 * @return void
	 */
	protected static function ensureTtl(string $key, int $expiration, int $requests): void
	{
		$cache = self::cache();
		if (!$cache)
		{
			return;
		}

		$ttl = $cache::ttl($key);
		if ($requests === 1 || $ttl < 0)
		{
			$cache::expire($key, $expiration);
		}
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
		$requests = static::increment($id);
		static::ensureTtl($id, $limit->getTimeLimit(), $requests);

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