<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Router\Request;

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
		sleep(1);
		return $next($request);
	}
}