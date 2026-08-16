<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\HttpTerminationException;
use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Tests\Test;

/**
 * RateLimiterTest
 *
 * Verifies fail-open (default) and fail-closed (security-critical)
 * behavior of {@see RateLimiter::check()} when the shared cache is
 * unavailable (e.g. Redis outage).
 *
 * @package Proto\Tests\Unit\Http
 */
final class RateLimiterTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		CacheOutageRateLimiter::exposeResetFallbackCounters();
	}

	/**
	 * A generic/default limiter must stay fail-open (unchanged behavior)
	 * when the cache is unavailable: no exception, no limiting applied,
	 * regardless of how many requests are made.
	 *
	 * @return void
	 */
	public function testGenericLimiterFailsOpenWhenCacheUnavailable(): void
	{
		$limit = Limit::perMinute(1)->by('fail-open-actor');

		for ($i = 0; $i < 5; $i++)
		{
			CacheOutageRateLimiter::exposeCheck($limit);
		}

		$this->assertTrue(true, 'No HttpTerminationException should be thrown for a fail-open limiter during a cache outage.');
	}

	/**
	 * A security-critical (fail-closed) limiter must keep limiting via
	 * the bounded fallback counter when the cache is unavailable, and
	 * deny (throw) once the limit is exceeded.
	 *
	 * @return void
	 */
	public function testFailClosedLimiterUsesFallbackCounterAndDenies(): void
	{
		$limit = Limit::perMinute(2)->by('fail-closed-actor')->failClosed();

		CacheOutageRateLimiter::exposeCheck($limit);
		CacheOutageRateLimiter::exposeCheck($limit);

		$this->expectException(HttpTerminationException::class);

		try
		{
			CacheOutageRateLimiter::exposeCheck($limit);
		}
		catch (HttpTerminationException $exception)
		{
			$this->assertSame(429, $exception->getStatusCode());
			throw $exception;
		}
	}

	/**
	 * Confirms the fail-closed flag defaults to false (BC: existing
	 * `Limit` usage without opting in stays fail-open).
	 *
	 * @return void
	 */
	public function testLimitDefaultsToFailOpen(): void
	{
		$limit = Limit::perMinute(5);
		$this->assertFalse($limit->isFailClosed());

		$limit->failClosed();
		$this->assertTrue($limit->isFailClosed());
	}
}

/**
 * Test double that always reports the shared cache as unavailable,
 * exercising the same `check()` logic via late static binding.
 *
 * @package Proto\Tests\Unit\Http
 */
final class CacheOutageRateLimiter extends RateLimiter
{
	/**
	 * @return string|null
	 */
	protected static function cache(): ?string
	{
		return null;
	}

	/**
	 * @param Limit $limit
	 * @return void
	 */
	public static function exposeCheck(Limit $limit): void
	{
		static::check($limit);
	}

	/**
	 * @return void
	 */
	public static function exposeResetFallbackCounters(): void
	{
		static::resetFallbackCounters();
	}
}
