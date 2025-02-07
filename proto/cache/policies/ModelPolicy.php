<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

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
	 * @param object $data The data object.
	 * @return object The updated model data.
	 */
	public function setup(object $data): object
	{
		$this->deleteAll();
		return $this->controller->setup($data);
	}

	/**
	 * Adds new model data.
	 *
	 * @param object $data The data object.
	 * @return object The newly added model data.
	 */
	public function add(object $data): object
	{
		$this->deleteAll();
		return $this->controller->add($data);
	}

	/**
	 * Merges new data into the model.
	 *
	 * @param object $data The data object.
	 * @return object The merged model data.
	 */
	public function merge(object $data): object
	{
		$this->deleteAll();
		return $this->controller->merge($data);
	}

	/**
	 * Updates model data.
	 *
	 * @param object $data The data object.
	 * @return object The updated model data.
	 */
	public function update(object $data): object
	{
		$id = $data->id ?? null;
		if ($id !== null)
		{
			$key = $this->createKey('get', $id);
			if ($this->hasKey($key))
			{
				$this->deleteKey($key);
			}
		}

		$this->deleteAll();
		return $this->controller->update($data);
	}

	/**
	 * Updates the model's status.
	 *
	 * @param int $id The model ID.
	 * @param mixed $status The new status value.
	 * @return object The updated model.
	 */
	public function updateStatus(int $id, mixed $status): object
	{
		$key = $this->createKey('get', $id);
		if ($this->hasKey($key))
		{
			$this->deleteKey($key);
		}

		$this->deleteAll();
		return $this->controller->updateStatus($id, $status);
	}

	/**
	 * Deletes model data.
	 *
	 * @param int|object $data The model ID or data object.
	 * @return object The deleted model.
	 */
	public function delete(int|object $data): object
	{
		$id = is_object($data) ? $data->id ?? null : $data;
		if ($id !== null)
		{
			$key = $this->createKey('get', $id);
			if ($this->hasKey($key))
			{
				$this->deleteKey($key);
			}
		}

		$this->deleteAll();
		return $this->controller->delete($data);
	}

	/**
	 * Retrieves model data.
	 *
	 * @param int $id The model ID.
	 * @return object The retrieved model.
	 */
	public function get(int $id): object
	{
		$key = $this->createKey('get', $id);
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		$response = $this->controller->get($id);
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
	 * @param array|null $modifiers The modifiers array.
	 * @return bool True if searching, otherwise false.
	 */
	protected function isSearching(?array $modifiers = null): bool
	{
		return !empty($modifiers['search']);
	}

	/**
	 * Builds a unique parameter string for cache keys.
	 *
	 * @param mixed $filter The filter criteria.
	 * @param int|null $offset The offset value.
	 * @param int|null $count The count value.
	 * @param array|null $modifiers The modifiers array.
	 * @return string The generated parameter string.
	 */
	public function setupAllParams(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
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

		if (!empty($modifiers))
		{
			$params[] = implode(':', $modifiers);
		}

		return implode(':', $params);
	}

	/**
	 * Retrieves model rows from the cache or database.
	 *
	 * @param mixed $filter The filter criteria.
	 * @param int|null $offset The offset value.
	 * @param int|null $count The count value.
	 * @param array|null $modifiers Additional modifiers.
	 * @return object The retrieved model rows.
	 */
	public function all(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): object
	{
		// Skip caching for searches
		if ($this->isSearching($modifiers))
		{
			return $this->controller->all($filter, $offset, $count, $modifiers);
		}

		$params = $this->setupAllParams($filter, $offset, $count, $modifiers);
		$key = $this->createKey('all', $params);
		if ($this->hasKey($key))
		{
			return $this->getValue($key);
		}

		$response = $this->controller->all($filter, $offset, $count, $modifiers);
		$this->setValue($key, $response, $this->expire);

		return $response;
	}
}