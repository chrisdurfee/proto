<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\Limit;
use Proto\Http\Middleware\ThrottleMiddleware;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * ThrottleMiddlewareTest
 *
 * ThrottleMiddleware used to sleep(1) per request (holding a worker and
 * limiting nobody). It now applies a non-blocking per-caller, per-route
 * rate limit.
 *
 * @package Proto\Tests\Unit\Http
 */
final class ThrottleMiddlewareTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testHandleDoesNotBlock(): void
	{
		$start = microtime(true);
		$result = (new ThrottleMiddleware())->handle(new Request(), fn(Request $request): string => 'next');

		$this->assertSame('next', $result);
		$this->assertLessThan(0.5, microtime(true) - $start, 'Throttle must not sleep.');
	}

	/**
	 * @return void
	 */
	public function testLimitIsPerRouteAndConfigurable(): void
	{
		$middleware = new class extends ThrottleMiddleware
		{
			protected int $requestsPerMinute = 5;

			public function exposeLimit(Request $request): Limit
			{
				return $this->getLimit($request);
			}
		};

		$limit = $middleware->exposeLimit(new Request());
		$this->assertSame(5, $limit->getRequestLimit());
		$this->assertSame(60, $limit->getTimeLimit());
		$this->assertStringStartsWith('throttle:', $limit->id());
	}
}
