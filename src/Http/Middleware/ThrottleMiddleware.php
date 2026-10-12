<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;
use Proto\Http\RateLimiterIdentity;
use Proto\Http\Router\Request;

/**
 * This constant defines the default delay (in seconds) for throttling requests.
 *
 * @deprecated ThrottleMiddleware no longer sleeps; kept for backward compatibility.
 */
const DEFAULT_THROTTLE_DELAY = 1;

/**
 * ThrottleMiddleware
 *
 * Throttles each caller (session user, else client IP) per route with a
 * non-blocking rate limit, answering 429 with `Retry-After` once the
 * limit is reached.
 *
 * This used to `sleep(1)` on every request, which held a PHP worker for
 * a second per call (a handful of clients could exhaust the pool) and
 * never actually limited anyone.
 *
 * Subclass and override `$requestsPerMinute` to change the limit.
 *
 * @package Proto\Http\Middleware
 */
class ThrottleMiddleware
{
	/**
	 * Requests allowed per caller, per route, per minute.
	 *
	 * @var int
	 */
	protected int $requestsPerMinute = 60;

	/**
	 * Handles the request by enforcing the throttle limit.
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
	 * Builds the per-caller, per-route limit.
	 *
	 * @param Request $request The incoming request.
	 * @return Limit
	 */
	protected function getLimit(Request $request): Limit
	{
		$route = strtoupper((string)$request->method()) . ' ' . (string)$request->path();
		$key = 'throttle:' . hash('xxh3', $route) . ':' . RateLimiterIdentity::resolve();
		return Limit::perMinute($this->requestsPerMinute)->by($key);
	}
}
