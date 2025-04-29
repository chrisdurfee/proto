<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Http\Router\Request;

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
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
		RateLimiter::check($this->getLimit($request));
		return $next($request);
	}

	/**
	 * Configures the login attempt limit.
	 *
	 * @param Request $request The incoming request.
	 * @return Limit The rate limit configuration.
	 */
	protected function getLimit(Request $request): Limit
	{
		$rateLimitKey = $request->input('username') . ':' . $request->ip();
		return Limit::perMinute(self::ATTEMPT_LIMIT)->by($rateLimitKey);
	}
}