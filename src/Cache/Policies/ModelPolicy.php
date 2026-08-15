<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

use Proto\Controllers\Controller;
use Proto\Http\Router\Request;

/**
 * ModelPolicy
 *
 * This class handles caching policies for models.
 *
 * The wrapped controller is always a resource controller at runtime, so the
 * resource CRUD methods (add, update, delete, get, all, etc.) are valid even
 * though the declared property type is the base Controller. The property is
 * typed as Controller to remain invariant with the parent policy (required by
 * PHP 8.4).
 *
 * @package Proto\Cache\Policies
 * @SuppressWarnings PHP0418
 */
class ModelPolicy extends Policy
{
	/**
	 * Creates a cache policy instance.
	 *
	 * @param Controller $controller The controller instance.
	 * @return void
	 */
	public function __construct(
		protected Controller $controller
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
		$this->invalidateGetKeys($request, $id, $item);

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
		$this->invalidateGetKeys($request, $id);

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
		$item = null;
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			$item = $this->controller->getRequestItem($request);
			$id = $item->id ?? null;
		}

		$this->invalidateGetKeys($request, $id, $item);

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
		$cacheId = $id ?? ($request->input('id') ?? $request->params()->id ?? null);
		$key = $this->createKey('get', $this->cacheIdWithIncludes($request, $cacheId));

		/**
		 * A single GET replaces the previous EXISTS + GET pair: setValue()
		 * never stores a literal null (@see Policy::setValue), so a null
		 * result here unambiguously means "not cached" — halving the Redis
		 * round trips on every cache hit.
		 */
		$cached = $this->getValue($key);
		if ($cached !== null)
		{
			$this->reenrichCached($cached, $request);
			return $cached;
		}

		$response = $this->controller->get($request);
		$this->setValue($key, $this->stripViewerFlags($response), $this->getMethodExpiration('get'));

		return $response;
	}

	/**
	 * Deletes all cached list keys.
	 *
	 * @return void
	 */
	protected function deleteAll(): void
	{
		$this->deleteKeysMatching($this->createKeyPattern('all', '*'));

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
				// Extract method name from cache key (format: Class:scope:method:params)
				$keyParts = explode(':', $key);
				if (count($keyParts) >= 3)
				{
					$method = $keyParts[2];
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
	 * Drop get() keys for an id, include suffixes, and slug/guid identities.
	 *
	 * `Class:*:get:5` does not match `Class:*:get:5:inc=author` or slug keys.
	 *
	 * @param Request $request
	 * @param mixed $id
	 * @param object|null $item
	 * @return void
	 */
	protected function invalidateGetKeys(Request $request, mixed $id = null, ?object $item = null): void
	{
		$identities = [];
		if ($id !== null && $id !== '')
		{
			$identities[] = $id;
		}

		if ($item !== null)
		{
			foreach (['id', 'guid', 'slug', 'uuid'] as $field)
			{
				$value = $item->$field ?? null;
				if ($value !== null && $value !== '')
				{
					$identities[] = $value;
				}
			}
		}

		$raw = $request->input('id') ?? $request->params()->id ?? null;
		if ($raw !== null && $raw !== '')
		{
			$identities[] = $raw;
		}

		foreach (array_unique($identities, SORT_REGULAR) as $identity)
		{
			$pattern = $this->createKeyPattern('get', $identity);
			$this->deleteKeysMatching($pattern);
			$this->deleteKeysMatching($pattern . ':*');
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
	 * @param array|null $modifiers The modifiers array.
	 * @return string The generated parameter string.
	 */
	public function setupAllParams(
		mixed $filter = null,
		?int $offset = null,
		?int $limit = null,
		?array $modifiers = null
	): string
	{
		$params = [];

		if ($filter !== null)
		{
			$params[] = $this->serializeFilterValue($filter);
		}

		if ($offset !== null)
		{
			$params[] = (string) $offset;
		}

		if ($limit !== null)
		{
			$params[] = (string) $limit;
		}

		if (!empty($modifiers))
		{
			$modParts = [];
			foreach ($modifiers as $key => $value)
			{
				if (is_array($value))
				{
					$modParts[] = $key . '=' . implode(',', $value);
				}
				else if (is_object($value))
				{
					$modParts[] = $key . '=' . json_encode($value);
				}
				else
				{
					$modParts[] = $key . '=' . (string) $value;
				}
			}
			$params[] = implode('|', $modParts);
		}

		return implode(':', $params);
	}

	/**
	 * Serializes a filter value into a stable string for cache key generation.
	 *
	 * Filters may be scalars, arrays, or objects (e.g. a decoded JSON filter
	 * such as {"tab":"forYou"}). Casting an object or nested array directly to
	 * string throws under PHP 8.4, so non-scalar values are JSON-encoded.
	 *
	 * @param mixed $filter The filter value.
	 * @return string
	 */
	protected function serializeFilterValue(mixed $filter): string
	{
		if (is_array($filter))
		{
			$parts = [];
			foreach ($filter as $value)
			{
				$parts[] = ($value === null || is_scalar($value))
					? (string) $value
					: (json_encode($value) ?: '');
			}
			return implode(':', $parts);
		}

		if (is_object($filter))
		{
			return json_encode($filter) ?: '';
		}

		return (string) $filter;
	}

	/**
	 * Retrieves model rows from the cache or database.
	 *
	 * @param Request $request The request object.
	 * @return object The retrieved model rows.
	 */
	public function all(Request $request): object
	{
		$inputs = $this->controller->getAllInputs($request);
		$filter = $inputs->filter;
		if (method_exists($this->controller, 'applyListScopes'))
		{
			$filter = $this->controller->applyListScopes($filter, $request);
		}
		$offset = $inputs->offset;
		$limit = $inputs->limit;
		$search = $inputs->modifiers['search'] ?? null;

		// Skip caching for searches
		if ($this->isSearching($search))
		{
			return $this->controller->all($request);
		}

		$params = $this->setupAllParams($filter, $offset, $limit, $this->modifiersWithIncludes($request, $inputs->modifiers));
		$key = $this->createKey('all', $params);

		$cached = $this->getValue($key);
		if ($cached !== null)
		{
			$this->reenrichCached($cached, $request);
			return $cached;
		}

		$response = $this->controller->all($request);
		$this->setValue($key, $this->stripViewerFlags($response), $this->getMethodExpiration('all'));

		return $response;
	}

	/**
	 * Fold allowlisted includes into the all() cache key.
	 *
	 * @param Request $request
	 * @param array|null $modifiers
	 * @return array|null
	 */
	protected function modifiersWithIncludes(Request $request, ?array $modifiers): ?array
	{
		if (!method_exists($this->controller, 'requestedIncludes'))
		{
			return $modifiers;
		}

		$includes = $this->controller->requestedIncludes($request);
		if ($includes === [])
		{
			return $modifiers;
		}

		$modifiers ??= [];
		$modifiers['include'] = $includes;
		return $modifiers;
	}

	/**
	 * Fold allowlisted includes into the get() cache key.
	 *
	 * @param Request $request
	 * @param mixed $id
	 * @return mixed
	 */
	protected function cacheIdWithIncludes(Request $request, mixed $id): mixed
	{
		if (!method_exists($this->controller, 'requestedIncludes'))
		{
			return $id;
		}

		$includes = $this->controller->requestedIncludes($request);
		if ($includes === [])
		{
			return $id;
		}

		return (string)$id . ':inc=' . implode(',', $includes);
	}

	/**
	 * Re-apply viewer flags after a shared-cache hit.
	 *
	 * @param mixed $response
	 * @param Request $request
	 * @return void
	 */
	protected function reenrichCached(mixed $response, Request $request): void
	{
		if (!is_object($response) || !method_exists($this->controller, 'usesSharedCache'))
		{
			return;
		}

		if (!$this->controller->usesSharedCache() || !method_exists($this->controller, 'reapplyEnrichments'))
		{
			return;
		}

		if (!empty($response->rows) && is_array($response->rows))
		{
			$this->controller->reapplyEnrichments($response->rows, $request);
		}

		if (isset($response->row) && is_object($response->row))
		{
			$rows = [$response->row];
			$this->controller->reapplyEnrichments($rows, $request);
			$response->row = $rows[0];
		}
	}

	/**
	 * Clone a response and strip viewer-specific fields before caching.
	 *
	 * @param mixed $response
	 * @return mixed
	 */
	protected function stripViewerFlags(mixed $response): mixed
	{
		if (!is_object($response) || !method_exists($this->controller, 'usesSharedCache') || !$this->controller->usesSharedCache())
		{
			return $response;
		}

		if (!method_exists($this->controller, 'viewerFlagFields'))
		{
			return $response;
		}

		$fields = $this->controller->viewerFlagFields();
		if ($fields === [])
		{
			return $response;
		}

		$copy = json_decode(json_encode($response));
		if (!is_object($copy))
		{
			return $response;
		}

		if (!empty($copy->rows) && is_array($copy->rows))
		{
			foreach ($copy->rows as $row)
			{
				if (!is_object($row))
				{
					continue;
				}

				foreach ($fields as $field)
				{
					unset($row->$field);
				}
			}
		}

		if (isset($copy->row) && is_object($copy->row))
		{
			foreach ($fields as $field)
			{
				unset($copy->row->$field);
			}
		}

		return $copy;
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
		// Use the framework's Request class instead of direct $_SERVER access
		return strtoupper(\Proto\Http\Request::method()) === 'GET';
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
		$cached = $this->getValue($key);
		if ($cached !== null)
		{
			return $cached;
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
	protected function generateGenericCacheParams(string $method, mixed $request = null): string
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
		$queryParams = ['filter', 'status', 'type', 'category', 'limit', 'offset', 'lastCursor', 'orderBy', 'groupBy', 'search'];
		foreach ($queryParams as $param)
		{
			$value = $request->input($param);
			if ($value !== null)
			{
				// handle array and object values
				if (is_array($value))
				{
					$value = implode(',', $value);
				}
				else if (is_object($value))
				{
					$value = json_encode($value);
				}
				$params[] = "{$param}:" . $value;
			}
		}

		// Include any additional parameters from the request
		$allInputs = $request->all();
		foreach ($allInputs as $key => $value)
		{
			if (!in_array($key, $queryParams) && $key !== 'id' && $value !== null)
			{
				if (is_array($value))
				{
					$value = implode(',', $value);
				}
				else if (is_object($value))
				{
					$value = json_encode($value);
				}

				$params[] = "{$key}:" . $value;
			}
		}

		return empty($params) ? 'no-params' : implode('|', $params);
	}
}