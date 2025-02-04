<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

/**
 * ModelPolicy
 *
 * This will create a policy for the model cache.
 *
 * @package Proto\Cache\Policies
 */
class ModelPolicy extends Policy
{
	/**
	 * This will add or update model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function setup(object $data): object
	{
		$this->deleteAll();
        return $this->controller->setup($data);
	}

	/**
	 * This will add model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function add(object $data): object
	{
		$this->deleteAll();
        return $this->controller->add($data);
	}

	/**
	 * This will add or update model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function merge(object $data): object
	{
		$this->deleteAll();
        return $this->controller->merge($data);
	}

    /**
	 * This will update model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function update(object $data): object
	{
		$id = $data->id ?? null;
		if (isset($id))
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
	 * This will update model item status.
	 *
	 * @param int $id
	 * @param mixed $status
	 * @return object
	 */
	public function updateStatus(int $id, $status): object
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
	 * This will delete model data.
	 *
	 * @param int|object $data
	 * @return object
	 */
	public function delete($data): object
	{
		$id = (gettype($data) === 'object')? $data->id : $data;

        $key = $this->createKey('get', $id);
        if ($this->hasKey($key))
        {
            $this->deleteKey($key);
        }

		$this->deleteAll();
		return $this->controller->delete($data);
	}

	/**
	 * This will get model data.
	 *
	 * @param int $id
	 * @return object
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
	 * This will remove all lists.
	 *
	 * @return void
	 */
	protected function deleteAll()
	{
		$key = $this->createKey('all', '*');
		$keys = $this->getKeys($key);
		if (!$keys || count($keys) < 1)
		{
			return;
		}

		foreach ($keys as $key)
		{
			$this->deleteKey($key);
		}
	}

	/**
	 * This will check if the modifiers are searching.
	 *
	 * @param array|null $modifiers
	 * @return bool
	 */
	protected function isSearching(?array $modifiers = null): bool
	{
		return (isset($modifiers) && isset($modifiers['search']));
	}

	/**
	 * This will get the all params.
	 *
	 * @param mixed $filter
	 * @param int $offset
	 * @param int $count
	 * @param array|null $modifiers
	 * @return string
	 */
	public function setupAllParams(
		$filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): string
	{
		$params = '';

		if (isset($filter))
		{
			switch (gettype($filter))
			{
				case 'array':
					$params .= implode(':', $filter);
					break;
				case 'string':
					$params .= $filter;
					break;
			}
		}

		if ($offset)
		{
			$params .= ':' . (string)$offset;
		}

		if ($count)
		{
			$params .= ':' . (string)$count;
		}

		if (isset($modifiers))
		{
			if (is_array($modifiers))
			{
				$params .= ':' . \implode(':', $modifiers);
			}
		}

		return $params;
	}

	/**
	 * This will get rows from a model.
	 *
	 * @param mixed $filter
	 * @param int $offset
	 * @param int $count
	 * @param array|null $modifiers
	 * @return object
	 */
	public function all(
		$filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): object
	{
		// this will not cache a search result
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