<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Middleware\RateLimiterMiddleware;
use Proto\Http\Limit;

/**
 * Router
 *
 * Handles HTTP routing and middleware integration.
 *
 * @package Proto\Http\Router
 */
class Router
{
	use MiddlewareTrait;

	/**
	 * @var string HTTP request method.
	 */
	protected string $method;

	/**
	 * @var string Base path for routing.
	 */
	protected string $basePath = '/';

	/**
	 * @var string Request path.
	 */
	protected string $path;

	/**
	 * @var array<Route> Registered routes.
	 */
	protected array $routes = [];

	/**
	 * Allowed HTTP methods.
	 *
	 * @var array<string>
	 */
	protected const METHODS = ['OPTIONS', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

	/**
	 * Initializes the router.
	 *
	 * @param string|null $basePath
	 * @param bool $requireHttps
	 */
	public function __construct(?string $basePath = null, bool $requireHttps = false)
	{
		$this->setupHeaders();
		$this->setBasePath($basePath);

		if ($requireHttps && !$this->isHttps())
		{
			$HTTPS_REQUIRED_CODE = 403;
			$this->sendResponse($HTTPS_REQUIRED_CODE, ['error' => 'HTTPS required.']);
		}

		$this->setupRequest();
	}

	/**
	 * Sets the base path.
	 *
	 * @param string|null $basePath
	 * @return void
	 */
	protected function setBasePath(?string $basePath = null): void
	{
		if ($basePath !== null)
		{
			$this->basePath = rtrim($basePath, '/');
		}
	}

	/**
	 * Checks if the request is over HTTPS.
	 *
	 * @return bool
	 */
	protected function isHttps(): bool
	{
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
	}

	/**
	 * Sets up response headers.
	 *
	 * @return void
	 */
	protected function setupHeaders(): void
	{
		header('Access-Control-Allow-Headers: *');
		header('Access-Control-Allow-Methods: ' . implode(', ', self::METHODS));
	}

	/**
	 * Initializes the request method and path.
	 *
	 * @return void
	 */
	protected function setupRequest(): void
	{
		$this->method = Request::method();
		$this->path = $this->stripBasePath(Request::path());

		if (!$this->isValidMethod($this->method))
		{
			$this->sendResponse(405, ['error' => 'Method Not Allowed']);
		}
	}

	/**
	 * Validates the request method.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function isValidMethod(string $method): bool
	{
		return in_array($method, self::METHODS, true);
	}

	/**
	 * Adds rate limiting middleware.
	 *
	 * @param Limit $limit
	 * @return self
	 */
	public function limit(Limit $limit): self
	{
		$this->middleware([new RateLimiterMiddleware($limit)]);
		return $this;
	}

	/**
	 * Strips the base path from the URI.
	 *
	 * @param string $uri
	 * @return string
	 */
	protected function stripBasePath(string $uri): string
	{
		return str_starts_with($uri, $this->basePath) ? substr($uri, strlen($this->basePath)) : $uri;
	}

	/**
	 * Checks if a given route matches the current request path and method.
	 *
	 * @param Uri $route
	 * @return bool
	 */
	protected function matchesRoute(Uri $route): bool
	{
		return $route->match($this->path, $this->method);
	}

	/**
	 * Registers a route.
	 *
	 * @param string $method
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	protected function addRoute(string $method, string $uri, callable $callback, ?array $middleware = null): self
	{
		$route = new Route($method, $uri, $callback);
		$this->routes[] = $route;

		if ($middleware !== null)
		{
			$route->middleware($middleware);
		}

		if ($this->matchesRoute($route))
		{
			$this->activateRoute($route);
		}

		return $this;
	}

	/**
	 * Registers a resource.
	 *
	 * @param string $uri
	 * @param string $controller
	 * @param array|null $middleware
	 * @return self
	 */
	public function resource(string $uri, string $controller, ?array $middleware = null): self
	{
		$callback = function($req, $params) use ($controller): mixed
		{
			$resource = new Resource($controller, $params);
			return $resource->activate($req);
		};

		$uri = $uri . '/:id?*';
		return $this->all($uri, $callback, $middleware);
	}

	/**
	 * Redirects a route.
	 *
	 * @param string $uri
	 * @param string $redirectUrl
	 * @param int $statusCode
	 * @return self
	 */
	public function redirect(string $uri, string $redirectUrl, int $statusCode = 301): self
	{
		$redirect = new Redirect($uri, $redirectUrl, $statusCode);

		if ($this->matchesRoute($redirect))
		{
			$this->activateRoute($redirect);
		}

		return $this;
	}

	/**
	 * Activates a route and executes its callback.
	 *
	 * @param Uri $route
	 * @return void
	 */
	protected function activateRoute(Uri $route): void
	{
		$result = $route->initialize($this->middleware, Request::class);
		if ($result !== null)
		{
			$statusCode = (int) ($result->code ?? 200);
			$this->sendResponse($statusCode, $result);
		}
	}

	/**
	 * Registers a GET route.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function get(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('GET', $uri, $callback, $middleware);
	}

	/**
	 * Registers a POST route.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function post(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('POST', $uri, $callback, $middleware);
	}

	/**
	 * Registers a PUT route.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function put(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('PUT', $uri, $callback, $middleware);
	}

	/**
	 * Registers a PATCH route.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function patch(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('PATCH', $uri, $callback, $middleware);
	}

	/**
	 * Registers a DELETE route.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function delete(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('DELETE', $uri, $callback, $middleware);
	}

	/**
	 * Registers a wildcard route that matches any HTTP method.
	 *
	 * @param string $uri
	 * @param callable $callback
	 * @param array|null $middleware
	 * @return self
	 */
	public function all(string $uri, callable $callback, ?array $middleware = null): self
	{
		return $this->addRoute('ALL', $uri, $callback, $middleware);
	}

	/**
	 * Sends a response and terminates execution.
	 *
	 * @param int $statusCode
	 * @param mixed $data
	 * @return void
	 */
	protected function sendResponse(int $statusCode, mixed $data = null): void
	{
		$response = new Response();
		$response->sendHeaders($statusCode)->json($data);
		exit;
	}
}