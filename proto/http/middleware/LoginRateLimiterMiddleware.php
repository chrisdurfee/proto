<?php declare(strict_types=1);

namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Http\Request;

/**
 * LoginRateLimiterMiddleware
 *
 * Middleware to limit the number of login attempts per minute.
 *
 * @package Proto\Http\Middleware
 */
class LoginRateLimiterMiddleware
{
	/**
	 * Maximum login attempts allowed per minute.
	 *
	 * @var int
	 */
	private const ATTEMPT_LIMIT = 10;

	/**
	 * Handles login rate limiting.
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
	 * Configures the login attempt limit.
	 *
	 * @return Limit The rate limit configuration.
	 */
	protected function getLimit(): Limit
	{
		$rateLimitKey = Request::input('username') . ':' . Request::ip();
		return Limit::perMinute(self::ATTEMPT_LIMIT)->by($rateLimitKey);
	}
}