<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Cache\Policies\Policy;
use Proto\Controllers\ControllerInterface;

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
	 * @var ControllerInterface $controller The controller instance associated with the resource.
	 */
	protected ControllerInterface $controller;

	/**
	 * @var ?Policy $policy The policy instance associated with the resource.
	 */
	protected ?Policy $policy = null;

	/**
	 * Initializes the route.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $uri The route URI.
	 * @param callable $callback The callback action to execute when the route is activated.
	 */
	public function __construct(
		string $controller
	)
	{
		$this->controller = ControllerHelper::getController($controller);
	}

	/**
	 * This will set the policy for the controller.
	 *
	 * @param string $policy
	 * @return self
	 */
	public function policy(
		string $policy
	): self
	{
		if (class_exists($policy))
		{
			$this->policy = new $policy($this->controller);
		}
		return $this;
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
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	protected function call(string $method, array $params = [])
	{
		if ($this->controllerHas($method))
		{
			return call_user_func_array([$this->controller, $method], $params);
		}

		$this->notFound("Method not found in the resource.");
		die;
	}

	/**
	 * This will return a 404 response.
	 *
	 * @return void
	 */
	protected function notFound(
		string $message = "Resource not found."
	): void
	{
		$statusCode = 404;
		$response = new Response();
		$response->sendHeaders($statusCode)->json([
			"message"=> $message,
			"success"=> false
		]);
	}

	/**
	 * Activates the route, executing the associated controller action.
	 *
	 * @param Request $request The request URI.
	 * @return mixed The result of the controller action.
	 */
	public function activate(Request $request): mixed
	{
		$item = $request->json('item');
		$resourceId = $request->params()->id ?? null;

		$method = $request->method();
		switch ($method)
		{
			case "GET":
				if ($resourceId === null)
				{
					return $this->call('all');
				}
				return $this->call('get', [$resourceId]);
			case "POST":
				return $this->call('add', [$item]);
			case "PUT":
				return $this->call('setup', [$item]);
			case "DELETE":
				$data = $item ?? $resourceId;
				return $this->call('delete', [$data]);
			case "PATCH":
				return $this->call('update', [$item]);
			default:
				$this->notFound();
				die;
		}
	}
}