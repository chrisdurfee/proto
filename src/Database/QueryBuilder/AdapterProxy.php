<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

use Proto\Database\Adapters\Adapter;
use Proto\Database\QueryBuilder\Select;
use Proto\Database\QueryBuilder\Insert;
use Proto\Database\QueryBuilder\Replace;
use Proto\Database\QueryBuilder\Update;
use Proto\Database\QueryBuilder\Delete;
use Proto\Storage\Filter;

/**
 * AdapterProxy
 *
 * Adds adapter functionality to the query builder.
 *
 * @mixin Select
 * @mixin Insert
 * @mixin Replace
 * @mixin Update
 * @mixin Delete
 * @package Proto\Database\QueryBuilder
 */
class AdapterProxy
{
	/**
	 * @var array
	 */
	protected array $params = [];

	/**
	 * Constructor.
	 *
	 * @param object $sql The SQL component.
	 * @param Adapter|null $db The database adapter.
	 * @return void
	 */
	public function __construct(protected ?object $sql, protected ?Adapter $db = null)
	{
	}

	/**
	 * Gets the query parameters.
	 *
	 * @return array
	 */
	public function &params(): array
	{
		return $this->params;
	}

	/**
	 * Adds a query parameter.
	 *
	 * @param mixed $value The parameter value.
	 * @return self
	 */
	public function addParam(mixed $value): self
	{
		$this->params[] = $value;
		return $this;
	}

	/**
	 * Adds query parameters.
	 *
	 * @param array $values The parameter values.
	 * @return self
	 */
	public function addParams(array $values): self
	{
		$this->params = array_merge($this->params, $values);
		return $this;
	}

	/**
	 * Prepends query parameters.
	 *
	 * @param array $values The parameter values.
	 * @return self
	 */
	public function prependParams(array $values): self
	{
		$this->params = array_merge($values, $this->params);
		return $this;
	}

	/**
	 * Magic method to handle dynamic method calls.
	 *
	 * Checks if the method is callable on the SQL object and calls it,
	 * returning the proxy for chaining.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
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
	 * Checks if a method is callable on a given object.
	 *
	 * @param object $object The object to check.
	 * @param string $method The method name.
	 * @return bool
	 */
	protected function isCallable(object $object, string $method): bool
	{
		return \is_callable([$object, $method]);
	}

	/**
	 * Converts the SQL object to a string.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return (string) $this->sql;
	}

	/**
	 * Checks if the adapter is set.
	 *
	 * @return bool
	 */
	protected function hasAdapter(): bool
	{
		return $this->db !== null;
	}

	/**
	 * Executes a query using the adapter.
	 *
	 * @param array $params The query parameters.
	 * @return bool
	 */
	public function execute(array $params = []): bool
	{
		if ($this->hasAdapter() === false)
		{
			return false;
		}

		$params = array_merge($this->params, $params);
		return $this->db->execute((string) $this->sql, $params);
	}

	/**
	 * Executes a transaction using the adapter.
	 *
	 * @param array $params The transaction parameters.
	 * @return bool
	 */
	public function transaction(array $params = []): bool
	{
		if ($this->hasAdapter() === false)
		{
			return false;
		}

		$params = array_merge($this->params, $params);
		return $this->db->transaction((string) $this->sql, $params);
	}

	/**
	 * Fetches data using the adapter.
	 *
	 * @param array $params The query parameters.
	 * @return array
	 */
	public function fetch(array $params = []): array
	{
		if ($this->hasAdapter() === false)
		{
			return [];
		}

		$params = array_merge($this->params, $params);
		return $this->db->fetch((string) $this->sql, $params);
	}

	/**
	 * Fetches the rows from the adapter.
	 *
	 * @param array $params The query parameters.
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
	 * Fetches the first row from the adapter.
	 *
	 * @param array $params The query parameters.
	 * @return object|null
	 */
	public function first(array $params = []): ?object
	{
		if ($this->hasAdapter() === false)
		{
			return null;
		}

		$this->sql->limit(1);
		$params = array_merge($this->params, $params);
		return $this->db->first((string) $this->sql, $params);
	}

	/**
	 * Sets the fields to be updated.
	 *
	 * @param mixed ...$fields The fields to set.
	 * @return self
	 */
	public function set(...$fields): self
	{
		$filteredFields = [];
		// update the fields to use filter if is array
		foreach ($fields as $field)
		{
			if (is_string($field))
			{
				$fieldList = explode(',', $field);
				$filteredFields[] = array_merge($filteredFields, $fieldList);
				continue;
			}

			if (is_array($field))
			{
				$field = Filter::formatForSet($field, $this->params);
			}

			$filteredFields[] = $field;
		}

		$this->sql->set(...$filteredFields);
		return $this;
	}

	/**
	 * Filters WHERE conditions.
	 *
	 * @param array $where The conditions to filter.
	 * @return array The filtered conditions.
	 */
	protected function filterWhereConditions(array $where): array
	{
		$filteredWhere = [];
		foreach ($where as $condition)
		{
			if (is_array($condition))
			{
				$condition = Filter::format($condition, $this->params);
			}

			$filteredWhere[] = $condition;
		}

		return $filteredWhere;
	}

	/**
	 * Adds WHERE conditions to the query with filter support.
	 *
	 * @param mixed ...$where One or more conditions, each as a string or array.
	 * @return self Returns the current instance.
	 */
	public function where(mixed ...$where): self
	{
		$filteredWhere = $this->filterWhereConditions($where);
		$this->sql->where(...$filteredWhere);

		return $this;
	}

	/**
	 * Adds AND conditions to the WHERE clause.
	 *
	 * @param mixed ...$where One or more conditions, each as a string or array.
	 * @return self Returns the current instance.
	 */
	public function andWhere(mixed ...$where): self
	{
		$filteredWhere = $this->filterWhereConditions($where);
		$this->sql->andWhere(...$filteredWhere);

		return $this;
	}

	/**
	 * Adds OR conditions to the WHERE clause.
	 *
	 * @param mixed ...$where One or more conditions, each as a string or array.
	 * @return self Returns the current instance.
	 */
	public function orWhere(mixed ...$where): self
	{
		$filteredWhere = $this->filterWhereConditions($where);
		$this->sql->orWhere(...$filteredWhere);

		return $this;
	}

	/**
	 * This will add a BETWEEN condition to the WHERE clause.
	 *
	 * @param string $columnName
	 * @param mixed $start
	 * @param mixed $end
	 * @return self
	 */
	public function whereBetween(string $columnName, mixed $start, mixed $end): self
	{
		if ($start !== "?")
		{
			$this->addParam($start);
			$start = "?";
		}

		if ($end !== "?")
		{
			$this->addParam($end);
			$end = "?";
		}

		$this->sql->whereBetween($columnName, $start, $end);
		return $this;
	}

	/**
	 * Add a JSON condition to the WHERE clause for a join, binding $params by reference.
	 *
	 * @param string $columnName The JSON column (e.g. "ou.organizations").
	 * @param mixed $value The value to encode (will become {"id":…} or similar).
	 * @param array<string> & $params The binding array that gets appended.
	 * @param string $path The JSON path, default '$'.
	 * @return self
	 */
	public function whereJoin(
		string $columnName,
		mixed $value,
		array &$params,
		string $path = '$'
	): self
	{
		$this->sql->whereJoin($columnName, $value, $params, $path);
		return $this;
	}
}