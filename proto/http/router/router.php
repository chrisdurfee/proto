<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Middleware\RateLimiterMiddleware;
use Proto\Http\Limit;

/**
 * Router
 *
 * This will handle the routing.
 *
 * @package Proto\Http\Router
 */
class Router
{
	use MiddlewareTrait;

	/**
	 * @var string $method
	 */
	protected string $method;

	/**
	 * @var string $basePath
	 */
	protected string $basePath;

	/**
	 * @var string $path
	 */
	protected string $path;

	/**
	 * @var array $routes
	 */
	protected array $routes = [];

	/**
	 * Allowed HTTP methods
	 *
	 * @var string[]
	 */
	protected const METHODS = [
		'OPTIONS',
		'GET',
		'POST',
		'PUT',
		'DELETE',
		'PATCH'
	];

	/**
	 * This will set up the router.
	 *
	 * @param string|null $basePath
	 * @param bool $https
	 * @return void
	 */
	public function __construct(?string $basePath = null, bool $https = false)
	{
		$this->setupHeaders();
		$this->setBasePath($basePath);

		if ($this->checkHttps($https))
		{
			$this->setupRequestSettings();
		}
	}

	/**
	 * Set the base path.
	 *
	 * @param string|null $basePath
	 * @return void
	 */
	protected function setBasePath(?string $basePath = null): void
	{
		if (isset($basePath))
		{
			$this->basePath = $basePath;
		}
	}

	/**
	 * Check if HTTPS is required.
	 *
	 * @param bool $https
	 * @return bool
	 */
	protected function checkHttps(bool $https): bool
	{
		if ($https && !isset($_SERVER['HTTPS']))
		{
			$FORBIDDEN_RESPONSE = 403;
			$this->response($FORBIDDEN_RESPONSE);
			return false;
		}
		return true;
	}

	/**
	 * Set up response headers.
	 *
	 * @return void
	 */
	protected function setupHeaders(): void
	{
		$methods = join(', ', self::METHODS);
		header('Access-Control-Allow-Headers: *');
		header('Access-Control-Allow-Methods: ' . $methods);
	}

	/**
	 * Set up request settings.
	 *
	 * @return void
	 */
	protected function setupRequestSettings(): void
	{
		$this->method = Request::method();
		$this->path = $this->removeBasePath(Request::path());

		if (!$this->checkServerMethod())
		{
			$this->response(405);
			return;
		}
	}

	/**
	 * Check if the request method is allowed.
	 *
	 * @return bool
	 */
	protected function checkServerMethod(): bool
	{
		return in_array($this->method, self::METHODS);
	}

	/**
	 * Add rate limiting middleware.
	 *
	 * @param Limit $limit
	 * @return self
	 */
	public function limit(Limit $limit): self
	{
		$this->addMiddleware([
			new RateLimiterMiddleware($limit)
		]);
		return $this;
	}

	/**
	 * Remove the base path from the URI.
	 *
	 * @param string $uri
	 * @return string
	 */
	protected function removeBasePath(string $uri): string
	{
		return isset($this->basePath) && $this->basePath !== '/' ? str_replace($this->basePath, '', $uri) : $uri;
	}

	/**
	 * Check if the route matches the path.
	 * @param Uri $route
	 * @return bool
	 */
	protected function match(Uri $route): bool
	{
		return $route->match($this->path, $this->method);
	}

	/**
	 * Add a route.
	 *
	 * @param string $method
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	protected function addRoute(string $method, string $uri, callable $callBack, ?array $middleware = null): self
	{
		$route = new Route($method, $uri, $callBack);
		array_push($this->routes, $route);

		if (isset($middleware))
		{
			$route->addMiddleware($middleware);
		}

		if ($this->match($route))
		{
			$this->activate($route);
		}

		return $this;
	}

	/**
	 * Redirect a route.
	 *
	 * @param string $uri
	 * @param string $redirectUrl
	 * @param int $responseCode
	 * @return self
	 */
	public function redirect(string $uri, string $redirectUrl, int $responseCode = 301): self
	{
		$redirect = new Redirect($uri, $redirectUrl, $responseCode);

		if ($this->match($redirect))
		{
			$this->activate($redirect);
		}

		return $this;
	}

	/**
	 * Activate the route.
	 *
	 * @param Uri $route
	 * @return void
	 */
	protected function activate(Uri $route): void
	{
		$result = $route->initialize($this->middleware, Request::class);
		if (isset($result))
		{
			$code = (int)($result->code ?? 200);
			$this->response($code, $result);
		}
	}

	/**
	 * Add a GET route.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function get(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('GET', $uri, $callBack, $middleware);
		return $this;
	}

	/**
	 * Add a PUT route.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function put(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('PUT', $uri, $callBack, $middleware);
		return $this;
	}

	/**
	 * Add a POST route.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function post(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('POST', $uri, $callBack, $middleware);
		return $this;
	}

	/**
	 * Add a PATCH route.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function patch(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('PATCH', $uri, $callBack, $middleware);
		return $this;
	}

		/**
	 * Add a DELETE route.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function delete(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('DELETE', $uri, $callBack, $middleware);
		return $this;
	}

	/**
	 * Add a route that matches on any method.
	 *
	 * @param string $uri
	 * @param callable $callBack
	 * @param array|null $middleware
	 * @return self
	 */
	public function all(string $uri, callable $callBack, ?array $middleware = null): self
	{
		$this->addRoute('ALL', $uri, $callBack, $middleware);
		return $this;
	}

	/**
	 * Return the route response.
	 *
	 * @param int $code
	 * @param mixed $data
	 * @return void
	 */
	protected function response(int $code, mixed $data = null): void
	{
		$response = new Response();
		$response
			->headers($code)
			->json($data);

		die;
	}
}