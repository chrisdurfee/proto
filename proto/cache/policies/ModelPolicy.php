<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

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
		$this->setValue($key, $response, $this->expire);

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
	}

	/**
	 * Determines if modifiers contain a search query.
	 *
	 * @param string|null $search The search query.
	 * @return bool True if searching, otherwise false.
	 */
	protected function isSearching(?string $search = null): bool
	{
		return !empty($modifiers['search']);
	}

	/**
	 * Builds a unique parameter string for cache keys.
	 *
	 * @param mixed $filter The filter criteria.
	 * @param int|null $offset The offset value.
	 * @param int|null $count The count value.
	 * @param string|null $search The search query.
	 * @return string The generated parameter string.
	 */
	public function setupAllParams(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?string $search = null
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

		if ($count !== null)
		{
			$params[] = (string) $count;
		}

		if (!empty($search))
		{
			$params[] = (string) $search;
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
		$offset = $request->getInt('start') ?? 0;
		$count = $request->getInt('count') ?? 50;
		$search = $request->input('search') ?? null;

		// Skip caching for searches
		if ($this->isSearching($search))
		{
			return $this->controller->all($request);
		}

		$params = $this->setupAllParams($filter, $offset, $count, $search);
		$key = $this->createKey('all', $params);
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		$response = $this->controller->all($request);
		$this->setValue($key, $response, $this->expire);

		return $response;
	}
}