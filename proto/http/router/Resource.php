<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Auth\PolicyProxy;
use Proto\Cache\Cache;
use Proto\Cache\Policies\ModelPolicy;
use Proto\Cache\Policies\Policy;
use Proto\Cache\Policies\PolicyProxy as CacheProxy;
use Proto\Controllers\Controller;

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
	 * @var object $controller The controller instance associated with the resource.
	 */
	protected object $controller;

	/**
	 * Initializes the route.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $uri The route URI.
	 * @param callable $callback The callback action to execute when the route is activated.
	 */
	public function __construct(
		string $controller,
		protected object $params
	)
	{
		$this->controller = $this->getController($controller);
	}

	/**
	 * This will set the caching policy for the controller.
	 *
	 * @param Controller $controller
	 * @param string $policy
	 * @return mixed
	 */
	protected function setCachingPolicy(
		Controller $controller,
		string $policy = ModelPolicy::class
	): mixed
	{
		if (Cache::isSupported() !== true)
		{
			return $controller;
		}

		/**
		 * @var object $cachePolicy
		 */
		$cachePolicy = new $policy($controller);
		return new CacheProxy($controller, $cachePolicy);
	}

	/**
	 * This will get the controller. If the controller has a policy
	 * defined, it will create a policy proxy to auth the actions
	 * before calling the methods.
	 *
	 * @param string $controller
	 * @return object
	 */
	public function getController(string $controller): object
	{
		$controller = new $controller();

		/**
		 * This will set up a caching policy for the controller.
		 */
		$controller = $this->setCachingPolicy($controller);

		/**
		 * This will check if the controller has a policy defined.
		 */
		$policy = $controller->getPolicy();
		if (!isset($policy))
		{
			return $controller;
		}

		/**
		 * This will create a policy proxy to auth the actions
		 * before calling the methods.
		 */
		return new PolicyProxy($controller, new $policy($controller));
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
	 * @param string $request The request URI.
	 * @return mixed The result of the controller action.
	 */
	public function activate(string $request): mixed
	{
		$item = $request::json('item');
		$resourceId = $this->params->id ?? null;

		$method = $request::method();
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
				return $this->call('delete', [$resourceId]);
			case "PATCH":
				return $this->call('update', [$item]);
			default:
				$this->notFound();
				die;
		}
	}
}