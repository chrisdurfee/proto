<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Http\Router\Request;

/**
 * RateLimiterMiddleware
 *
 * Middleware to enforce request rate limiting.
 *
 * @package Proto\Http\Middleware
 */
class RateLimiterMiddleware
{
	/**
	 * Initializes the rate limiter middleware.
	 *
	 * @param Limit $limit The rate limiting configuration.
	 */
	public function __construct(
		private Limit $limit
	)
	{
	}

	/**
	 * Handles the rate limiting check.
	 *
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
		RateLimiter::check($this->getLimit());
		return $next($request);
	}

	/**
	 * Retrieves the rate limiting configuration.
	 *
	 * @return Limit The configured request limit.
	 */
	protected function getLimit(): Limit
	{
		return $this->limit;
	}
}