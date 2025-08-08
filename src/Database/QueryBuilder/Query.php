<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

use Proto\Database\Adapters\Sql\Mysql\MysqliBindTrait;

/**
 * Query
 *
 * Base abstract class for building database queries.
 *
 * This class provides methods for constructing query parts such as joins, conditions,
 * ordering, and limits. It extends the Template class and uses the MysqliBindTrait
 * for binding values.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Query extends Template
{
	use MysqliBindTrait;

	/**
	 * Query conditions.
	 *
	 * @var array<int, string>
	 */
	protected array $conditions = [];

	/**
	 * Table name for the query.
	 *
	 * @var string
	 */
	protected string $tableName;

	/**
	 * Table alias for the query.
	 *
	 * @var string|null
	 */
	protected ?string $alias = null;

	/**
	 * Join clauses.
	 *
	 * @var array<int, string>
	 */
	protected array $joins = [];

	/**
	 * Selected fields.
	 *
	 * @var array<int, string>
	 */
	protected array $fields = [];

	/**
	 * ORDER BY clauses.
	 *
	 * @var array<int, string>
	 */
	protected array $orderBy = [];

	/**
	 * LIMIT clause.
	 *
	 * @var string
	 */
	protected string $limit = '';

	/**
	 * Constructs a new Query instance.
	 *
	 * @param string $tableName The name of the table.
	 * @param string|null $alias The alias for the table.
	 */
	public function __construct(string $tableName, ?string $alias = null)
	{
		$this->tableName = $tableName;
		$this->alias = $alias ?? $tableName;
	}

	/**
	 * Retrieves the table alias.
	 *
	 * @return string|null The table alias.
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}

	/**
	 * Adds a field to the query.
	 *
	 * This method allows adding a field either as a raw SQL string or as an array
	 * defining the field and its alias.
	 *
	 * @param mixed  $fieldDefinition The field definition (string or array).
	 * @param string $alias The alias to use for the field.
	 * @return void
	 */
	protected function addField(mixed $fieldDefinition, string $alias): void
	{
		$column = '';

		if (is_array($fieldDefinition))
		{
			$count = count($fieldDefinition);
			if ($count < 2)
			{
				$column = $fieldDefinition[0];
			}
			else if (is_array($fieldDefinition[0]))
			{
				$column = $fieldDefinition[0][0] . ' AS ' . $fieldDefinition[1];
			}
			else
			{
				$fieldSql = $fieldDefinition[0] . ' AS ' . $fieldDefinition[1];
				$column   = $alias . '.' . $fieldSql;
			}
		}
		else
		{
			$column = $alias . '.' . $fieldDefinition;
		}

		$this->fields[] = $column;
	}

	/**
	 * Gets the comparison string for a condition.
	 *
	 * If the value specification is an array, it builds a comparison string based on its length.
	 *
	 * @param string|array $valueSpec The value specification.
	 * @return string The comparison string.
	 */
	protected function getCompareString(string|array $valueSpec): string
	{
		if (!is_array($valueSpec))
		{
			return $valueSpec;
		}

		$count = count($valueSpec);
		switch ($count)
		{
			case 3:
				$comparison = implode(' ', $valueSpec);
				break;
			case 2:
				$comparison = $valueSpec[0] . ' = ' . $valueSpec[1];
				break;
			default:
				$comparison = $valueSpec[0];
		}
		return $comparison;
	}

	/**
	 * Retrieves the join table name.
	 *
	 * @param array $join The join configuration array.
	 * @return string The join table name.
	 */
	protected function getJoinTableName(array $join): string
	{
		return (is_array($join['table'])) ? '(' . $join['table'][0] . ')' : $join['table'];
	}

	/**
	 * Builds join clauses using a callback to configure a JoinBuilder.
	 *
	 * @param callable $callBack A callback that receives a JoinBuilder instance.
	 * @return void
	 */
	protected function joinBuilder(callable $callBack): void
	{
		$joins   = [];
		$builder = new JoinBuilder($joins);

		call_user_func($callBack, $builder);

		foreach ($joins as $join)
		{
			$fields = $join->getFields();
			if ($fields)
			{
				$joinAlias = $join->getAlias();
				$this->addJoinFields($fields, $joinAlias);
			}

			$this->joins[] = (string) $join;
		}
	}

	/**
	 * Adds a join clause to the query.
	 *
	 * @param array|callable $join The join configuration array or a callback for building joins.
	 * @return self Returns the current instance.
	 */
	public function join(array|callable $join): self
	{
		if (is_callable($join))
		{
			$this->joinBuilder($join);
			return $this;
		}

		if (empty($join))
		{
			return $this;
		}

		$type = isset($join['type']) ? strtoupper($join['type']) : 'INNER JOIN';
		$tableSql = $this->getJoinTableName($join);
		$tableAlias = empty($join['alias']) ? '' : 'AS ' . $join['alias'];
		$alias = $join['alias'] ?? $tableSql;

		$on = isset($join['using'])
			? ' ' . $join['using']
			: (!empty($join['on']) ? ' ON ' . $this->getOnString($join['on']) . ' ' : '');

		$sql = ' ' . $type . ' ' . $tableSql . ' ' . $alias . $on;
		$this->joins[] = $sql;

		$fields = $join['fields'] ?? null;
		if ($fields)
		{
			$this->addJoinFields($fields, $alias);
		}

		return $this;
	}

	/**
	 * Adds a left join clause to the query.
	 *
	 * @param array $join The join configuration array.
	 * @return self Returns the current instance.
	 */
	public function leftJoin(array $join): self
	{
		$join['type'] = 'left join';
		return $this->join($join);
	}

	/**
	 * Adds a right join clause to the query.
	 *
	 * @param array $join The join configuration array.
	 * @return self Returns the current instance.
	 */
	public function rightJoin(array $join): self
	{
		$join['type'] = 'right join';
		return $this->join($join);
	}

	/**
	 * Adds an outer join clause to the query.
	 *
	 * @param array $join The join configuration array.
	 * @return self Returns the current instance.
	 */
	public function outerJoin(array $join): self
	{
		$join['type'] = 'outer join';
		return $this->join($join);
	}

	/**
	 * Adds a cross join clause to the query.
	 *
	 * @param array $join The join configuration array.
	 * @return self Returns the current instance.
	 */
	public function crossJoin(array $join): self
	{
		$join['type'] = 'cross join';
		return $this->join($join);
	}

	/**
	 * Adds multiple join clauses to the query.
	 *
	 * @param array $joins An array of join configuration arrays.
	 * @return self Returns the current instance.
	 */
	public function joins(array $joins): self
	{
		if (empty($joins))
		{
			return $this;
		}

		foreach ($joins as $join)
		{
			$this->join($join);
		}

		return $this;
	}

	/**
	 * Retrieves the table string including alias if applicable.
	 *
	 * @return string The table string.
	 */
	protected function getTableString(): string
	{
		return ($this->alias === $this->tableName)
			? $this->tableName
			: $this->tableName . ' AS ' . $this->alias;
	}

	/**
	 * Constructs the ON clause string for join conditions.
	 *
	 * @param array|null $on An array of conditions for the join.
	 * @return string The ON clause string.
	 */
	protected function getOnString(?array $on): string
	{
		if (!$on)
		{
			return '';
		}

		$onParts = array_map([$this, 'getCompareString'], $on);
		return implode(' AND ', $onParts);
	}

	/**
	 * Adds fields from join clauses to the query.
	 *
	 * @param array|null $fields An array of fields to add.
	 * @param string|null $alias The alias for the join table.
	 * @return void
	 */
	protected function addJoinFields(?array $fields = null, ?string $alias = null): void
	{
		if (empty($fields))
		{
			return;
		}

		foreach ($fields as $field)
		{
			// Convert pre-aliased fields to raw SQL if necessary.
			if (is_string($field) && strpos($field, '.') !== false)
			{
				$field = [$field];
			}
			$this->addField($field, $alias);
		}
	}

	/**
	 * Constructs a property string from an array of values.
	 *
	 * @param array $propertyAn array of property values.
	 * @param string $propertyText The text label for the property.
	 * @param string $glueText The glue string to join property values.
	 * @return string The constructed property string.
	 */
	protected function getPropertyString(array $property, string $propertyText, string $glueText): string
	{
		return empty($property)
			? ''
			: ' ' . $propertyText . ' ' . implode($glueText, $property);
	}

	/**
	 * Adds WHERE conditions to the query.
	 *
	 * @param mixed ...$where One or more conditions, each as a string or array.
	 * @return self Returns the current instance.
	 */
	public function where(mixed ...$where): self
	{
		if (empty($where))
		{
			return $this;
		}

		foreach ($where as $conditionSpec)
		{
			$condition = $this->getCompareString($conditionSpec);
			$this->conditions[] = $condition;
		}

		return $this;
	}

	/**
	 * This will add a JSON condition to the WHERE clause.
	 *
	 * @param string $columnName
	 * @param array|object $value
	 * @param mixed $path
	 * @return self
	 */
	public function whereJson(string $columnName, array|object $value, ?string $path = '$'): self
	{
		$encodedValue = json_encode($value);
		$condition = "JSON_CONTAINS({$columnName}, '{$encodedValue}', '{$path}')";
		$this->conditions[] = $condition;
		return $this;
	}

	/**
	 * This will add a JSON condition to the WHERE clause for a join.
	 *
	 * @param string $columnName
	 * @param mixed $value
	 * @param array $params
	 * @param mixed $path
	 * @return self
	 */
	public function whereJoin(
		string $columnName,
		mixed $value,
		array &$params,
		?string $path = '$'
	): self
	{
		/**
		 * This will create the JSON condition for a join.
		 */
		$encodedValue = json_encode((object)$value);
		$params[] = $encodedValue;

		// Use a placeholder for the JSON value in the condition
		$condition = "JSON_CONTAINS({$columnName}, ?, '{$path}')";
		$this->conditions[] = $condition;
		return $this;
	}

	/**
	 * Adds ORDER BY clauses to the query.
	 *
	 * @param mixed ...$columns One or more columns for ordering, each as a string or an array.
	 * @return self Returns the current instance.
	 */
	public function orderBy(mixed ...$columns): self
	{
		if (empty($columns))
		{
			return $this;
		}

		foreach ($columns as $orderSpec)
		{
			if (!is_array($orderSpec))
			{
				$orderBy = $orderSpec;
			}
			else
			{
				$orderBy = $orderSpec[0] . ' ' . strtoupper($orderSpec[1]);
			}

			$this->orderBy[] = $orderBy;
		}

		return $this;
	}

	/**
	 * Adds an IN clause to the WHERE conditions.
	 *
	 * @param string $columnName The column name for the IN clause.
	 * @param array $fields The array of values for the IN clause.
	 * @return self Returns the current instance.
	 */
	public function in(string $columnName, array $fields): self
	{
		$placeholders = implode(',', array_fill(0, count($fields), '?'));
		$condition = "{$columnName} IN ({$placeholders})";
		$this->conditions[] = $condition;
		return $this;
	}

	/**
	 * Adds a LIMIT clause to the query.
	 *
	 * @param int|null $offset The offset for the LIMIT clause.
	 * @param int|null $count The count for the LIMIT clause.
	 * @return self Returns the current instance.
	 */
	public function limit(?int $offset = null, ?int $count = null): self
	{
		if (is_null($offset))
		{
			return $this;
		}

		$this->limit = " LIMIT " . $offset;

		if (!is_null($count))
		{
			$this->limit .= ", " . $count;
		}

		return $this;
	}
}