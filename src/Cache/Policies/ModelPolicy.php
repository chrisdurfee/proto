<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;

/**
 * ModelPolicy
 *
 * This class handles caching policies for models.
 *
 * @package Proto\Cache\Policies
 */
class ModelPolicy extends Policy
{
	/**
	 * Creates a cache policy instance.
	 *
	 * @param ResourceController $controller The controller instance.
	 * @return void
	 */
	public function __construct(
		protected ResourceController $controller
	)
	{
	}

	/**
	 * Adds or updates model data.
	 *
	 * @param Request $request The request object.
	 * @return object The updated model data.
	 */
	public function setup(Request $request): object
	{
		$this->deleteAll();
		return $this->controller->setup($request);
	}

	/**
	 * Adds new model data.
	 *
	 * @param Request $request The request object.
	 * @return object The newly added model data.
	 */
	public function add(Request $request): object
	{
		$this->deleteAll();
		return $this->controller->add($request);
	}

	/**
	 * Merges new data into the model.
	 *
	 * @param Request $request The request object.
	 * @return object The merged model data.
	 */
	public function merge(Request $request): object
	{
		$this->deleteAll();
		return $this->controller->merge($request);
	}

	/**
	 * Retrieves the resource ID from the request.
	 *
	 * @param Request $request The request object.
	 * @return int|null The resource ID, or null if not found.
	 */
	protected function getResourceId(Request $request): ?int
	{
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}

	/**
	 * Updates model data.
	 *
	 * @param Request $request The request object.
	 * @return object The updated model data.
	 */
	public function update(Request $request): object
	{
		$item = $this->controller->getRequestItem($request);
		$id = $item->id ?? $this->getResourceId($request);
		if ($id !== null)
		{
			$key = $this->createKey('get', $id);
			if ($this->hasKey($key))
			{
				$this->deleteKey($key);
			}
		}

		$this->deleteAll();
		return $this->controller->update($request);
	}

	/**
	 * Updates the model's status.
	 *
	 * @param Request $request The request object.
	 * @return object The updated model.
	 */
	public function updateStatus(Request $request): object
	{
		$id = $this->getResourceId($request);
		$key = $this->createKey('get', $id);
		if ($this->hasKey($key))
		{
			$this->deleteKey($key);
		}

		$this->deleteAll();

		/**
		 * @SuppressWarnings PHP0406
		 * @SuppressWarnings PHP0423
		 */
		return $this->controller->updateStatus($request);
	}

	/**
	 * Deletes model data.
	 *
	 * @param Request $request The request object.
	 * @return object The deleted model.
	 */
	public function delete(Request $request): object
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			$item = $this->controller->getRequestItem($request);
			$id = $item->id ?? null;
		}

		if ($id !== null)
		{
			$key = $this->createKey('get', $id);
			if ($this->hasKey($key))
			{
				$this->deleteKey($key);
			}
		}

		$this->deleteAll();
		return $this->controller->delete($request);
	}

	/**
	 * Retrieves model data.
	 *
	 * @param Request $request The request object.
	 * @return object The retrieved model.
	 */
	public function get(Request $request): object
	{
		$id = $this->getResourceId($request);
		$key = $this->createKey('get', $id);
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		$response = $this->controller->get($request);
		$this->setValue($key, $response, $this->getMethodExpiration('get'));

		return $response;
	}

	/**
	 * Deletes all cached list keys.
	 *
	 * @return void
	 */
	protected function deleteAll(): void
	{
		$keyPattern = $this->createKey('all', '*');
		$keys = $this->getKeys($keyPattern);
		if (!empty($keys))
		{
			foreach ($keys as $key)
			{
				$this->deleteKey($key);
			}
		}

		// Also clear any generic method caches that might be affected
		$this->deleteGenericMethodCaches();
	}

	/**
	 * Deletes cached generic method keys.
	 *
	 * @return void
	 */
	protected function deleteGenericMethodCaches(): void
	{
		// Get all cache keys for this controller
		$controllerPrefix = $this->controller::class . ':';
		$allKeys = $this->getKeys($controllerPrefix . '*');

		if (!empty($allKeys))
		{
			$standardMethods = ['get', 'all', 'setup', 'add', 'merge', 'update', 'updateStatus', 'delete'];

			foreach ($allKeys as $key)
			{
				// Extract method name from cache key
				$keyParts = explode(':', $key);
				if (count($keyParts) >= 2)
				{
					$method = $keyParts[1];
					// If it's not a standard CRUD method, it's likely a generic cached method
					if (!in_array($method, $standardMethods))
					{
						$this->deleteKey($key);
					}
				}
			}
		}
	}

	/**
	 * Determines if modifiers contain a search query.
	 *
	 * @param string|null $search The search query.
	 * @return bool True if searching, otherwise false.
	 */
	protected function isSearching(?string $search = null): bool
	{
		return !empty($search);
	}

	/**
	 * Builds a unique parameter string for cache keys.
	 *
	 * @param mixed $filter The filter criteria.
	 * @param int|null $offset The offset value.
	 * @param int|null $limit The count value.
	 * @param string|null $search The search query.
	 * @param array|null $custom Custom parameters.
	 * @param string|null $lastCursor The last cursor value.
	 * @return string The generated parameter string.
	 */
	public function setupAllParams(
		mixed $filter = null,
		?int $offset = null,
		?int $limit = null,
		?string $search = null,
		?array $custom = null,
		?string $lastCursor = null
	): string
	{
		$params = [];

		if ($filter !== null)
		{
			$params[] = is_array($filter) ? implode(':', $filter) : (string) $filter;
		}

		if ($offset !== null)
		{
			$params[] = (string) $offset;
		}

		if ($limit !== null)
		{
			$params[] = (string) $limit;
		}

		if (!empty($search))
		{
			$params[] = (string) $search;
		}

		if (!empty($custom))
		{
			$params[] = implode(':', $custom);
		}

		if (!empty($lastCursor))
		{
			$params[] = (string) $lastCursor;
		}

		return implode(':', $params);
	}

	/**
	 * Retrieves model rows from the cache or database.
	 *
	 * @param Request $request The request object.
	 * @return object The retrieved model rows.
	 */
	public function all(Request $request): object
	{
		$filter = $this->controller->getFilter($request);
		$offset = $request->getInt('offset') ?? 0;
		$limit = $request->getInt('limit') ?? 50;
		$search = $request->input('search') ?? null;
		$custom = $request->input('custom') ?? null;
		$lastCursor = $request->input('lastCursor') ?? null;

		// Skip caching for searches
		if ($this->isSearching($search))
		{
			return $this->controller->all($request);
		}

		$params = $this->setupAllParams($filter, $offset, $limit, $search, $custom, $lastCursor);
		$key = $this->createKey('all', $params);
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		$response = $this->controller->all($request);
		$this->setValue($key, $response, $this->getMethodExpiration('all'));

		return $response;
	}

	/**
	 * Magic method to handle dynamic method calls.
	 * This method provides generic caching for non-standard GET requests.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The result of the method call.
	 */
	public function __call(string $method, array $arguments): mixed
	{
		// Check if this is a GET request for generic caching
		if ($this->isGetRequest() && method_exists($this->controller, $method))
		{
			return $this->handleGenericGetRequest($method, $arguments);
		}

		// For non-GET requests or methods that don't exist, call controller directly
		return $this->controller->{$method}(...$arguments);
	}

	/**
	 * Checks if the current request is a GET request.
	 *
	 * @return bool True if it's a GET request, otherwise false.
	 */
	protected function isGetRequest(): bool
	{
		return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
	}

	/**
	 * Handles generic caching for GET requests.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The cached or fresh result.
	 */
	protected function handleGenericGetRequest(string $method, array $arguments): mixed
	{
		// Generate a cache key based on method name and serialized arguments
		$request = $arguments[0] ?? null;
		$cacheParams = $this->generateGenericCacheParams($method, $request);
		$key = $this->createKey($method, $cacheParams);

		// Check if we have a cached result
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		// Call the controller method and cache the result
		$response = $this->controller->{$method}(...$arguments);
		$this->setValue($key, $response, $this->getMethodExpiration($method));

		return $response;
	}

	/**
	 * Generates cache parameters for generic methods.
	 *
	 * @param string $method The method name.
	 * @param Request|null $request The request object.
	 * @return string The generated cache parameters.
	 */
	protected function generateGenericCacheParams(string $method, $request = null): string
	{
		if (!$request || !($request instanceof Request))
		{
			return 'no-params';
		}

		$params = [];

		// Include common request parameters that might affect the response
		$id = $this->getResourceId($request);
		if ($id !== null)
		{
			$params[] = "id:{$id}";
		}

		// Include query parameters that might affect caching
		$queryParams = ['filter', 'status', 'type', 'category', 'limit', 'offset', 'lastCursor'];
		foreach ($queryParams as $param)
		{
			$value = $request->input($param);
			if ($value !== null)
			{
				$params[] = "{$param}:" . (is_array($value) ? implode(',', $value) : $value);
			}
		}

		// Include any additional parameters from the request
		$allInputs = $request->all();
		foreach ($allInputs as $key => $value)
		{
			if (!in_array($key, $queryParams) && $key !== 'id' && $value !== null)
			{
				$params[] = "{$key}:" . (is_array($value) ? implode(',', $value) : $value);
			}
		}

		return empty($params) ? 'no-params' : implode('|', $params);
	}
}