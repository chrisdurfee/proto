<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Http\Request;

/**
 * LoginRateLimiterMiddleware
 *
 * This will limit the number of login attempts per minute.
 *
 * @package Proto\Http\Middleware
 */
class LoginRateLimiterMiddleware
{
	/**
	 * This will be called when the middleware is activated.
	 *
	 * @param string $request
	 * @param callable $next
	 * @return mixed
	 */
	public function handle(string $request, callable $next): mixed
	{
		/**
		 * This will limit the number of login attempts per minute
		 * by IP and username.
		 */
		$id = Request::input('username') .':' . Request::ip();

		/**
		 * This will set up the limit.
		 */
		$ATTEMPT_LIMIT = 10;
		$limit = Limit::perMinute($ATTEMPT_LIMIT)
			->by($id);

		RateLimiter::check($limit);

		return $next($request);
	}
}
