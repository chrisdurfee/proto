<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Resource
 *
 * Represents a specific Resource with its associated HTTP method and
 * callback action.
 *
 * @package Proto\Http\Router
 */
class Resource
{
	/**
	 * Initializes the route.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $uri The route URI.
	 * @param callable $callback The callback action to execute when the route is activated.
	 */
	public function __construct(
		protected string $controller,
		protected object $params
	)
	{
	}

	/**
	 * This will check if the controller has the method.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function controllerHas(string $method)
	{
		return is_callable([$this->controller, $method]);
	}

	/**
	 * This will call the controller method.
	 *
	 * @param string $request
	 * @param string $method
	 * @param mixed $resourceId
	 * @return mixed
	 */
	protected function call(string $request, string $method, mixed $resourceId = null)
	{
		if ($this->controllerHas($method))
		{
			return call_user_func([$this->controller, $method], $request, $resourceId);
		}

		$this->notFound();
		die;
	}

	/**
	 * This will return a 404 response.
	 *
	 * @return void
	 */
	protected function notFound(): void
	{
		$statusCode = 404;
		$response = new Response();
		$response->sendHeaders($statusCode)->json([
			"message"=> "Resource not found.",
			"success"=> false
		]);
	}

	/**
	 * Activates the route, executing the associated controller action.
	 *
	 * @param string $request The request URI.
	 * @return mixed The result of the controller action.
	 */
	public function activate(string $request): mixed
	{
		$resourceId = $this->params->id ?? null;
		$method = $request::method();
		switch ($method)
		{
			case "GET":
				if ($resourceId !== null)
				{
					return $this->call($request, 'all');
				}
				return $this->call($request, 'get', $resourceId);
			case "POST":
				return $this->call($request, 'post', $resourceId);
			case "PUT":
				return $this->call($request, 'put', $resourceId);
			case "DELETE":
				return $this->call($request, 'delete', $resourceId);
			case "PATCH":
				return $this->call($request, 'patch', $resourceId);
			default:
				$this->notFound();
				die;
		}
	}
}