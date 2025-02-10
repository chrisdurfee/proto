<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Select
 *
 * This class handles SELECT queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Select extends FieldQuery
{
	/**
	 * The list of fields for the query.
	 *
	 * @var string[]
	 */
	protected array $fields = [];

	/**
	 * The DISTINCT clause, if applied.
	 *
	 * @var string
	 */
	protected string $distinct = '';

	/**
	 * The HAVING conditions for the query.
	 *
	 * @var string[]
	 */
	protected array $having = [];

	/**
	 * The GROUP BY fields for the query.
	 *
	 * @var string[]
	 */
	protected array $groupBy = [];

	/**
	 * The forced index clause, if applied.
	 *
	 * @var string
	 */
	protected string $index = '';

	/**
	 * The UNION clauses for the query.
	 *
	 * @var string[]
	 */
	protected array $unions = [];

	/**
	 * Adds columns to the SELECT query.
	 *
	 * If no fields are provided, defaults to '*'.
	 *
	 * @param mixed ...$fields The fields to select.
	 * @return self Returns the current instance.
	 */
	public function select(...$fields): self
	{
		if (count($fields) < 1)
		{
			$fields[] = '*';
		}

		foreach ($fields as $field)
		{
			$this->addField($field, $this->alias);
		}

		return $this;
	}

	/**
	 * Alias for select() to add columns.
	 *
	 * @param mixed ...$fields The fields to select.
	 * @return self Returns the current instance.
	 */
	public function fields(...$fields): self
	{
		return $this->select(...$fields);
	}

	/**
	 * Forces the use of a specific index.
	 *
	 * @param string $index The index to force.
	 * @return self Returns the current instance.
	 */
	public function forceIndex(string $index): self
	{
		$this->index = ' FORCE INDEX(' . $index . ') ';
		return $this;
	}

	/**
	 * Sets the query to return distinct results.
	 *
	 * @return self Returns the current instance.
	 */
	public function distinct(): self
	{
		$this->distinct = ' DISTINCT ';
		return $this;
	}

	/**
	 * Adds GROUP BY fields to the query.
	 *
	 * @param mixed ...$columns The columns to group by.
	 * @return self Returns the current instance.
	 */
	public function groupBy(...$columns): self
	{
		if (count($columns) < 1)
		{
			return $this;
		}

		foreach ($columns as $column)
		{
			$this->groupBy[] = $column;
		}

		return $this;
	}

	/**
	 * Adds HAVING conditions to the query.
	 *
	 * @param string|array ...$having The HAVING conditions.
	 * @return self Returns the current instance.
	 */
	public function having(string|array ...$having): self
	{
		if (count($having) < 1)
		{
			return $this;
		}

		foreach ($having as $conditionSpec)
		{
			$condition = $this->getCompareString($conditionSpec);
			$this->having[] = $condition;
		}

		return $this;
	}

	/**
	 * Adds a UNION clause to the query.
	 *
	 * @param string|object $sql The SQL to union.
	 * @return self Returns the current instance.
	 */
	public function union(string|object $sql): self
	{
		if (!$sql)
		{
			return $this;
		}

		$this->unions[] = 'UNION ' . (string)$sql;
		return $this;
	}

	/**
	 * Adds a UNION ALL clause to the query.
	 *
	 * @param string|object $sql The SQL to union.
	 * @return self Returns the current instance.
	 */
	public function unionAll(string|object $sql): self
	{
		if (!$sql)
		{
			return $this;
		}

		$this->unions[] = 'UNION ALL ' . (string)$sql;
		return $this;
	}

	/**
	 * Renders the complete SELECT query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$fields = implode(', ', $this->fields);
		$from = $this->getTableString();
		$joins = implode(' ', $this->joins);
		$unions = implode(' ', $this->unions);
		$where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
		$orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');
		$groupBy = $this->getPropertyString($this->groupBy, ' GROUP BY ', ', ');
		$having = $this->getPropertyString($this->having, ' HAVING ', ' AND ');

		return 'SELECT' . $this->distinct . $fields .
			' FROM ' . $from . $this->index .
			' ' . $joins .
			$where .
			$groupBy .
			$having .
			$orderBy .
			' ' . $unions .
			' ' . $this->limit;
	}
}