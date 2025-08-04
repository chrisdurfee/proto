<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Cte
 *
 * This class handles the CTE (Common Table Expression) query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Cte extends Template
{
	/**
	 * The query for the CTE, which can be a Query object or a string.
	 *
	 * @var Query|string|null
	 */
	protected Query|string|null $query = null;

	/**
	 * The recursive flag for the CTE.
	 *
	 * @var string
	 */
	protected string $recursive = '';

	/**
	 * The name of the CTE.
	 *
	 * @var string
	 */
	protected string $cteName;

	/**
	 * Creates the CTE with the given name.
	 *
	 * @param string $cteName The name of the CTE.
	 */
	public function __construct(string $cteName)
	{
		$this->cteName = $cteName;
	}

	/**
	 * Creates a SELECT query builder for the specified table and sets it as the CTE query.
	 *
	 * @param array|string $tableName The table name.
	 * @param string|null $alias The table alias.
	 * @return Select The SELECT query builder.
	 */
	public function table(array|string $tableName, ?string $alias = null): Select
	{
		$query = new Select($tableName, $alias);
		$this->query = $query;
		return $query;
	}

	/**
	 * Sets the query string for the CTE.
	 *
	 * @param string $query The query string.
	 */
	public function query(string $query): void
	{
		$this->query = $query;
	}

	/**
	 * Enables or disables recursive mode for the CTE.
	 *
	 * @param bool $recursive Whether the CTE should be recursive.
	 * @return self
	 */
	public function recursive(bool $recursive = true): self
	{
		$this->recursive = $recursive === true ? 'RECURSIVE' : '';
		return $this;
	}

	/**
	 * Renders the CTE query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$query = (string)$this->query;
		return "{$this->recursive} {$this->cteName} AS ({$query})";
	}
}