<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Uri
 *
 * Abstract class responsible for managing routes and their associated functionality.
 *
 * @package Proto\Http\Router
 * @abstract
 */
abstract class Uri
{
	use MiddlewareTrait;

	/**
	 * @var ?object $params Route parameters.
	 */
	protected ?object $params = null;

	/**
	 * @var array<string> $paramNames Names of the route parameters.
	 */
	protected array $paramNames = [];

	/**
	 * @var string $uri The route URI.
	 */
	protected string $uri;

	/**
	 * @var string|null $method The HTTP method associated with the route.
	 */
	protected ?string $method = null;

	/**
	 * @var string $uriQuery The compiled regex pattern for matching URIs.
	 */
	protected string $uriQuery = '';

	/**
	 * Initializes the route.
	 *
	 * @param string $uri The route URI.
	 */
	public function __construct(string $uri)
	{
		$this->uri = $uri;
		$this->setupParamKeys($uri);
		$this->setupUriQuery($uri);
	}

	/**
	 * Compiles the URI pattern into a regex for matching.
	 *
	 * @param string $uri The route URI.
	 * @return void
	 */
	protected function setupUriQuery(string $uri): void
	{
		if ($uri === '')
		{
			$this->uriQuery = '/.*/';
			return;
		}

		// Escape slashes
		$uriQuery = preg_replace('/\//', '\/', $uri);
		// Replace optional parameters
		$uriQuery = preg_replace('/:(\w+)\?/', '(?P<\1>[^\/]*)?', $uriQuery);
		// Replace required parameters
		$uriQuery = preg_replace('/:(\w+)/', '(?P<\1>[^\/]+)', $uriQuery);
		// Wildcard match
		$uriQuery = str_replace('*', '.*', $uriQuery);

		$this->uriQuery = '/^' . $uriQuery . '$/';
	}

	/**
	 * Extracts and stores the names of route parameters.
	 *
	 * @param string $uri The route URI.
	 * @return void
	 */
	protected function setupParamKeys(string $uri): void
	{
		preg_match_all('/:(\w+)\??/', $uri, $matches);
		$this->paramNames = $matches[1] ?? [];
	}

	/**
	 * Stores the matched parameters from the request.
	 *
	 * @param array<string> $matches The regex matches.
	 * @return void
	 */
	protected function setParams(array $matches): void
	{
		if (!empty($matches) && !empty($this->paramNames))
		{
			$this->params = (object) array_combine($this->paramNames, array_slice($matches, 1));
		}
	}

	/**
	 * Retrieves route parameters.
	 *
	 * @return ?object
	 */
	protected function getParams(): ?object
	{
		return $this->params;
	}

	/**
	 * Initializes the route and executes middleware.
	 *
	 * @param array $globalMiddleWare The global middleware to apply.
	 * @param string $request The request URI.
	 * @return mixed
	 */
	public function initialize(array $globalMiddleWare, string $request): mixed
	{
		$middleware = array_merge($globalMiddleWare, $this->middleware);

		if (empty($middleware))
		{
			return $this->activate($request);
		}

		// Reduce middleware array into callable chain
		$next = array_reduce(
			array_reverse($middleware),
			fn ($next, $item) => fn ($req) => $item->handle($req, $next),
			fn ($req) => $this->activate($req)
		);

		return $next($request);
	}

	/**
	 * Activates the route and processes the request.
	 *
	 * @param string $request The request URI.
	 * @return mixed
	 */
	abstract public function activate(string $request): mixed;

	/**
	 * Checks if the request method matches the route method.
	 *
	 * @param string $method The HTTP method to check.
	 * @return bool
	 */
	protected function checkMethod(string $method): bool
	{
		if ($this->method === null || strtolower($this->method) === 'all')
		{
			return true;
		}

		return strtolower($this->method) === strtolower($method);
	}

	/**
	 * Checks if a given URI and method match the route.
	 *
	 * @param string $uri The request URI.
	 * @param string $method The HTTP method.
	 * @return bool
	 */
	public function match(string $uri, string $method): bool
	{
		if (!$this->checkMethod($method))
		{
			return false;
		}

		$matches = [];
		$result = preg_match($this->uriQuery, $uri, $matches);

		if ($result === 1)
		{
			$this->setParams($matches);
		}

		return $result === 1;
	}
}