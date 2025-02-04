<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Limit;
use Proto\Http\RateLimiter;

/**
 * RateLimiterMiddleware
 *
 * This will limit the number of requests per minute.
 *
 * @package Proto\Http\Middleware
 */
class RateLimiterMiddleware
{
	/**
	 * @var Limit $limit
	 */
	protected Limit $limit;

	/**
	 * This will set up the middleware.
	 *
	 * @param Limit $limit
	 * @return void
	 */
	public function __construct(Limit $limit)
	{
		$this->limit = $limit;
	}

	/**
	 * This will be called when the middleware is activated.
	 *
	 * @param string $request
	 * @param callable $next
	 * @return mixed
	 */
	public function handle(string $request, callable $next): mixed
	{
		RateLimiter::check($this->limit);
		return $next($request);
	}
}
