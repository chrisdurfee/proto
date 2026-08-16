<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Cache\Cache;

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
	 * In-process fallback counters, keyed by rate-limit id, used when the
	 * shared cache is unavailable and no fallback storage (e.g. APCu) is
	 * available either. Bounded per-process; not shared across workers.
	 *
	 * @var array<string, array{count: int, expires: int}>
	 */
	protected static array $fallbackCounters = [];

	/**
	 * Checks if the rate limit is exceeded.
	 *
	 * When the shared cache is unavailable, this fails open (no
	 * limiting, unchanged/default behavior) unless the limit is marked
	 * {@see Limit::failClosed()}, in which case it falls back to a
	 * bounded in-process/APCu counter so security-critical limiters
	 * (login, password-reset, OTP) never silently stop limiting.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	public static function check(Limit $limit): void
	{
		$cache = static::cache();
		if ($cache === null)
		{
			static::logCacheOutage($limit);

			if (!$limit->isFailClosed())
			{
				return;
			}

			static::checkFallback($limit);
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
	 * Logs a cache-outage condition encountered while rate limiting.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	protected static function logCacheOutage(Limit $limit): void
	{
		error_log(sprintf(
			'RateLimiter: cache unavailable, %s for limit id "%s".',
			$limit->isFailClosed() ? 'failing closed (using fallback counter)' : 'failing open (no limiting applied)',
			$limit->id()
		));
	}

	/**
	 * Enforces a fail-closed limit using a bounded fallback counter
	 * (APCu when available, otherwise an in-process counter) while the
	 * shared cache is unavailable.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	protected static function checkFallback(Limit $limit): void
	{
		$id = 'rate-limit:fallback:' . $limit->id();
		$requests = static::fallbackIncrement($id, $limit->getTimeLimit());

		if ($limit->isOverLimit($requests))
		{
			static::sendRateLimitResponse($limit, $requests);
		}
	}

	/**
	 * Increments a bounded fallback counter, preferring APCu (shared
	 * across requests within a process/worker pool) and falling back to
	 * a static in-process counter (bounded to the current process's
	 * lifetime) when APCu is unavailable, e.g. in a CLI test run.
	 *
	 * @param string $key
	 * @param int $expiration
	 * @return int
	 */
	protected static function fallbackIncrement(string $key, int $expiration): int
	{
		if (function_exists('apcu_enabled') && apcu_enabled())
		{
			apcu_add($key, 0, $expiration);
			$count = apcu_inc($key);
			return $count === false ? 1 : $count;
		}

		return static::inProcessIncrement($key, $expiration);
	}

	/**
	 * Increments a per-process fallback counter with a fixed-window TTL.
	 *
	 * @param string $key
	 * @param int $expiration
	 * @return int
	 */
	protected static function inProcessIncrement(string $key, int $expiration): int
	{
		$now = time();
		$entry = static::$fallbackCounters[$key] ?? null;

		if ($entry === null || $entry['expires'] <= $now)
		{
			static::$fallbackCounters[$key] = ['count' => 1, 'expires' => $now + $expiration];
			return 1;
		}

		$entry['count']++;
		static::$fallbackCounters[$key] = $entry;
		return $entry['count'];
	}

	/**
	 * Clears in-process fallback counters. Test helper.
	 *
	 * @return void
	 */
	protected static function resetFallbackCounters(): void
	{
		static::$fallbackCounters = [];
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
	 * Terminates the request with a rate-limit-exceeded response.
	 *
	 * Throws {@see HttpTerminationException} instead of rendering a
	 * response and calling `exit` directly, so the failure is
	 * unit-testable and any `finally`/rollback around the call site
	 * still runs. The exception is caught once at the router's dispatch
	 * entry point ({@see \Proto\Http\Router\Router::activateRoute()}),
	 * which renders the identical 429 response this produced before.
	 * Rate-limit headers are sent immediately, ahead of the shared
	 * status/Content-Type headers the exception sends when caught.
	 *
	 * @param Limit $limit
	 * @param int $requests
	 * @return never
	 * @throws HttpTerminationException Always.
	 */
	protected static function sendRateLimitResponse(Limit $limit, int $requests): never
	{
		self::setRateHeaders($limit, $requests);

		$data = (object)[
			'message' => 'Too Many Requests',
			'success' => false
		];

		throw new HttpTerminationException($data, 429);
	}
}