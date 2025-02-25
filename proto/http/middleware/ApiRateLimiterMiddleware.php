<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;

/**
 * ApiRateLimiterMiddleware
 *
 * Middleware to enforce API rate limits.
 *
 * @package Proto\Http\Middleware
 */
class ApiRateLimiterMiddleware
{
	/**
	 * Maximum allowed requests per minute.
	 *
	 * @var int
	 */
	private const MAX_REQUESTS = 100_000;

	/**
	 * Handles API rate limiting.
	 *
	 * @param string $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(string $request, callable $next): mixed
	{
		RateLimiter::check($this->getLimit());
		return $next($request);
	}

	/**
	 * Defines the rate limit for the middleware.
	 *
	 * @return Limit The rate limit configuration.
	 */
	protected function getLimit(): Limit
	{
		$maxRequests = env('router')->maxRequests ?? self::MAX_REQUESTS;
		return Limit::perMinute($maxRequests);
	}
}