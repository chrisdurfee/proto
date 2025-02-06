<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * MiddlewareTrait
 *
 * Provides functionality for handling middleware in a request lifecycle.
 *
 * @package Proto\Http\Router
 */
trait MiddlewareTrait
{
	/**
	 * Middleware stack.
	 *
	 * @var array<string>
	 */
	protected array $middleware = [];

	/**
	 * Adds middleware to the stack.
	 *
	 * @param array<string> $middleware Array of middleware class names.
	 * @return self
	 */
	public function addMiddleware(array $middleware): self
	{
		$this->middleware = array_merge($this->middleware, $middleware);
		return $this;
	}

	/**
	 * Sets up a middleware callback.
	 *
	 * @param string $middleware Middleware class name.
	 * @param callable $next Next middleware in the stack.
	 * @return callable
	 */
	protected function setupMiddlewareCallback(string $middleware, callable $next): callable
	{
		if (!class_exists($middleware) || !method_exists($middleware, 'handle'))
		{
			return fn($request) => $next($request); // If middleware is invalid, pass through.
		}

		return function($request) use ($middleware, $next)
		{
			return (new $middleware())->handle($request, $next);
		};
	}
}