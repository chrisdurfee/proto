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
	public function middleware(array $middleware): self
	{
		$this->middleware = array_merge($this->middleware, $middleware);
		return $this;
	}

	/**
	 * Shared cache of instantiated middleware objects, keyed by class name.
	 *
	 * Reusing the same instance across requests avoids repeated construction
	 * overhead for stateless middleware classes.
	 *
	 * @var array<string, object>
	 */
	private static array $middlewareInstances = [];

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
			$instance = static::$middlewareInstances[$middleware] ??= new $middleware();
			return $instance->handle($request, $next);
		};
	}
}