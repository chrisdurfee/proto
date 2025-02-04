<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * MiddlewareTrait
 *
 * MiddlewareTrait provides basic functionality for handling middleware.
 *
 * @package Proto\Http\Router
 */
trait MiddlewareTrait
{
    /**
     * @var array $middleware The middleware stack.
     */
    protected array $middleware = [];

    /**
     * Add middleware to the stack.
     *
     * @param array $middleware An array of middleware classes.
     * @return self
     */
    public function addMiddleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Setup a middleware callback.
     *
     * @param string $middleware The middleware class.
     * @param callable $next The next middleware in the stack.
     * @return callable
     */
    protected function setupMiddlewareCallback(
        string $middleware,
        callable $next
    ): callable
    {
        return function($request) use($middleware, $next)
        {
            return call_user_func([new $middleware, 'handle'], $request, $next);
        };
    }
}