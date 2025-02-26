<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Route
 *
 * Represents a specific route with its associated HTTP method and
 * callback action.
 *
 * @package Proto\Http\Router
 */
class Route extends Uri
{
	/**
	 * @var callable The callback action to execute when the route is activated.
	 */
	protected $callback;

	/**
	 * Initializes the route.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $uri The route URI.
	 * @param callable $callback The callback action to execute when the route is activated.
	 */
	public function __construct(string $method, string $uri, callable $callback)
	{
		parent::__construct($uri);
		$this->setMethod($method);
		$this->callback = $callback;
	}

	/**
	 * Sets the HTTP method for the route.
	 *
	 * @param string $method The HTTP method.
	 * @return void
	 */
	private function setMethod(string $method): void
	{
		$method = strtoupper($method);
		$this->method = $method;
	}

	/**
	 * Gets the HTTP method for the route.
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Activates the route, executing the associated callback action.
	 *
	 * @param string $request The request URI.
	 * @return mixed The result of the callback action.
	 */
	public function activate(string $request): mixed
	{
		return call_user_func($this->callback, $request, $this->params);
	}
}