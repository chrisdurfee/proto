<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Route
 *
 * Route is a concrete class extending Uri, representing
 * a specific route with its associated HTTP method and
 * callback action.
 *
 * @package Proto\Http\Router
 */
class Route extends Uri
{
	/**
	 * @var string $method The HTTP method for the route.
	 */
	protected string $method;

	/**
	 * @var callable $callBack The callback action to execute when the route is activated.
	 */
	protected $callBack;

	/**
	 * This will set up the route.
	 *
	 * @param string $method The HTTP method for the route.
	 * @param string $uri The route URI.
	 * @param callable $callBack The callback action to execute when the route is activated.
	 * @return void
	 */
	public function __construct(string $method, string $uri, callable $callBack)
	{
		parent::__construct($uri);

		$this->method = $method;
		$this->callBack = $callBack;
	}

	/**
	 * Activate the route, executing the associated callback action.
	 *
	 * @param string $request The request URI.
	 * @return mixed The result of the callback action.
	 */
	public function activate(string $request): mixed
	{
		return call_user_func($this->callBack, $request, $this->params);
	}
}