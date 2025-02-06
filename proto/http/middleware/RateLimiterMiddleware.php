<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;

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
	 * The request limit configuration.
	 */
	private Limit $limit;

	/**
	 * Initializes the rate limiter middleware.
	 *
	 * @param Limit $limit The rate limiting configuration.
	 */
	public function __construct(Limit $limit)
	{
		$this->limit = $limit;
	}

	/**
	 * Handles the rate limiting check.
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
	 * Retrieves the rate limiting configuration.
	 *
	 * @return Limit The configured request limit.
	 */
	protected function getLimit(): Limit
	{
		return $this->limit;
	}
}