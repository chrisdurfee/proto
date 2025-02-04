<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;

/**
 * ApiRateLimiterMiddleware
 *
 * This will limit the number of requests per minute.
 *
 * @package Proto\Http\Middleware
 */
class ApiRateLimiterMiddleware
{
	/**
	 * @var int This will set the maximum requests.
	 */
	const MAX_REQUESTS = 100000;

	/**
	 * This will be called when the middleware is activated.
	 *
	 * @param string $request
	 * @param callable $next
	 * @return mixed
	 */
	public function handle(string $request, callable $next): mixed
	{
		RateLimiter::check(Limit::perMinute(self::MAX_REQUESTS));
		return $next($request);
	}
}
