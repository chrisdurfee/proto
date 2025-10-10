<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Router\Request;

/**
 * This constant defines the default delay (in seconds) for throttling requests.
 */
const DEFAULT_THROTTLE_DELAY = 1;

/**
 * ThrottleMiddleware
 *
 * Middleware to throttle requests by introducing a delay.
 *
 * @package Proto\Http\Middleware
 */
class ThrottleMiddleware
{
	/**
	 * Handles the request by introducing a delay.
	 *
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
		sleep(DEFAULT_THROTTLE_DELAY);
		return $next($request);
	}
}