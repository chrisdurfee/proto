<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * AdapterProxy
 *
 * This will add adapter functionality to the query builder.
 *
 * @package Proto\Database\QueryBuilder
 */
class AdapterProxy
{
    /**
     * This will construct the adapter proxy.
     *
     * @param object $sql
     * @param object|null $db
     * @return void
     */
    public function __construct(
        protected ?object $sql,
        protected ?object $db = null)
    {
    }

    /**
     * This will check to call the method and normalizew the result.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!$this->isCallable($this->sql, $method))
        {
            return $this;
        }

        \call_user_func_array([$this->sql, $method], $arguments);
        return $this;
    }

    /**
     * This will check if a method is callable.
     *
     * @param object $object
     * @param string $method
     * @return bool
     */
    protected function isCallable(object $object, string $method): bool
    {
        return \is_callable([$object, $method]);
    }

    /**
     * This will convert the object to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->sql;
    }

    /**
     * This will check if the adapter is set.
     *
     * @return bool
     */
    protected function hasAdapter(): bool
    {
        return (is_null($this->db) === false);
    }

    /**
	 * This will execute a query from the connection.
	 *
	 * @param array $params
	 * @return bool
	 */
	public function execute(array $params = []): bool
	{
        if ($this->hasAdapter() === false)
        {
            return false;
        }

		return $this->db->execute((string)$this->sql, $params);
	}

    /**
	 * This will make a transaction from the connection.
	 *
	 * @param array $params
	 * @return bool
	 */
	public function transaction(array $params = []): bool
	{
        if ($this->hasAdapter() === false)
        {
            return false;
        }

		return $this->db->transaction((string)$this->sql, $params);
	}

    /**
	 * This will fetch from the connection.
	 *
	 * @param array $params
	 * @return array|bool
	 */
	public function fetch(array $params = []): mixed
	{
        if ($this->hasAdapter() === false)
        {
            return false;
        }

		return $this->db->fetch((string)$this->sql, $params);
	}

	/**
	 * This will fetch the rows from the connection.
	 *
	 * @param array $params
	 * @return array
	 */
	public function rows(array $params = []): array
	{
        if ($this->hasAdapter() === false)
        {
            return [];
        }

		return $this->fetch($params) ?? [];
	}

    /**
	 * This will fetch the first row from the connection.
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function first(array $params = []): mixed
	{
        if ($this->hasAdapter() === false)
        {
            return [];
        }

        $this->sql->limit(1);
		$result = $this->db->fetch((string)$this->sql, $params);
		return $result[0] ?? null;
	}
}