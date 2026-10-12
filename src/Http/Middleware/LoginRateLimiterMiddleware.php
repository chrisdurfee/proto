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
	 * Maximum login attempts per account per hour, from any IP.
	 *
	 * Bounds password guessing spread across many addresses (botnets,
	 * rotating proxies), which the per-IP limit alone cannot see.
	 *
	 * @var int
	 */
	private const ACCOUNT_ATTEMPT_LIMIT = 100;

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

		$accountLimit = $this->getAccountLimit($request);
		if ($accountLimit !== null)
		{
			RateLimiter::check($accountLimit);
		}

		return $next($request);
	}

	/**
	 * Configures the login attempt limit.
	 *
	 * Marked {@see Limit::failClosed()} because login is
	 * security-critical: if the shared cache is unavailable, this must
	 * keep limiting via the bounded fallback counter rather than
	 * silently allowing unlimited login attempts.
	 *
	 * @param Request $request The incoming request.
	 * @return Limit The rate limit configuration.
	 */
	protected function getLimit(Request $request): Limit
	{
		$rateLimitKey = $request->input('username') . ':' . $request->ip();
		return Limit::perMinute(self::ATTEMPT_LIMIT)->by($rateLimitKey)->failClosed();
	}

	/**
	 * Configures the per-account limit (independent of client IP), or
	 * null when the request names no account.
	 *
	 * @param Request $request The incoming request.
	 * @return Limit|null
	 */
	protected function getAccountLimit(Request $request): ?Limit
	{
		$username = $request->input('username');
		if (!is_string($username) || trim($username) === '')
		{
			return null;
		}

		$key = 'login-account:' . hash('sha256', strtolower(trim($username)));
		return Limit::perHour(self::ACCOUNT_ATTEMPT_LIMIT)->by($key)->failClosed();
	}
}
