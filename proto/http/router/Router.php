<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Middleware\RateLimiterMiddleware;
use Proto\Http\Limit;

/**
 * HttpStatus Enum
 *
 * Defines standard HTTP status codes.
 */
enum HttpStatus: int
{
	case OK = 200;
	case BAD_REQUEST = 400;
	case UNAUTHORIZED = 401;
	case FORBIDDEN = 403;
	case NOT_FOUND = 404;
	case METHOD_NOT_ALLOWED = 405;
	case TOO_MANY_REQUESTS = 429;
	case INTERNAL_SERVER_ERROR = 500;
}

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
	 * @param Request $request
	 * @return void
	 */
	public function __construct(
		?string $basePath = null,
		bool $requireHttps = false,
		protected $request = new Request()
	)
	{
		Headers::set(self::METHODS);
		$this->setBasePath($basePath);

		if ($requireHttps && !$this->isHttps())
		{
			$this->sendResponse(HttpStatus::FORBIDDEN->value, ['error' => 'HTTPS required.']);
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
	 * Initializes the request method and path.
	 *
	 * @return void
	 */
	protected function setupRequest(): void
	{
		$this->method = $this->request->method();
		$this->path = $this->filterPath($this->request->path());

		if (!$this->isValidMethod($this->method))
		{
			$this->sendResponse(HttpStatus::METHOD_NOT_ALLOWED->value, ['error' => 'Method Not Allowed']);
		}
	}

	/**
	 * Filters the request path to remove query parameters.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function filterPath(string $path): string
	{
		$path = rtrim($path, '/');
		return explode('?', $path)[0];
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
	 * This will return the full URI.
	 *
	 * @param string $uri
	 * @return string
	 */
	protected function getUri(string $uri): string
	{
		return $this->basePath . '/' . $this->stripBasePath($uri);
	}

	/**
	 * This will return the full URI.
	 *
	 * @param string $uri
	 * @return callable
	 */
	protected function checkArrayCallback(callable $callback): callable
	{
		if (!is_array($callback) || !is_string($callback[0] ?? null) && !is_string($callback[1] ?? null))
		{
			return $callback;
		}

		[$class, $methodName] = $callback;
		return function(Request $req) use ($class, $methodName)
		{
			/**
			 * The controller will be set up using the helper to help
			 * cache and apply the controller policy if it exists.
			 */
			$controller = ControllerHelper::getController($class);
			if (!is_callable([$controller, $methodName]))
			{
				$this->sendResponse(HttpStatus::NOT_FOUND->value, ['error' => 'Method not found in the resource.']);
				return;
			}
			return $controller->{$methodName}($req);
		};
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
		/**
		 * This will update any array callbacks.
		 */
		$callback = $this->checkArrayCallback($callback);

		$uri = $this->getUri($uri);
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
		$callback = function(Request $req) use ($controller): mixed
		{
			$resource = new Resource($controller);
			return $resource->activate($req);
		};

		$uri = $uri . '/:id?';
		return $this->all($uri, $callback, $middleware);
	}

	/**
	 * Creates a group URL by appending the URI to the base path.
	 *
	 * @param string $oldBase
	 * @param string $uri
	 * @return string
	 */
	protected function createGroupUrl(string $oldBase, string $uri): string
	{
		return rtrim($oldBase, '/') . '/' . trim($uri, '/');
	}

	/**
	 * Register a group of routes under a common URI prefix.
	 *
	 * @param string $uri URI segment (no leading/trailing slash)
	 * @param callable $callback Receives $this to add routes
	 * @param array|null $middleware Middleware to apply to all routes in group
	 * @return self
	 */
	public function group(string $uri, callable $callback, ?array $middleware = null): self
	{
		/**
		 * The base url will be updated to the group URI and will be reset
		 * after the callback is executed.
		 */
		$oldBase = $this->basePath;
		$oldMiddleware = $this->middleware;

		$this->basePath = $this->createGroupUrl($oldBase, $uri);
		if ($middleware !== null)
		{
			$this->middleware($middleware);
		}

		$callback($this);

		$this->basePath = $oldBase;
		$this->middleware = $oldMiddleware;

		return $this;
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
		$uri = $this->getUri($uri);
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
		$result = $route->initialize($this->middleware, $this->request);
		if ($result !== null)
		{
			$statusCode = (is_int($result->code ?? '')) ? $result->code : HttpStatus::OK->value;
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
		$response->json($data, $statusCode);
		exit;
	}
}