<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;

/**
 * Class ModelJoin
 *
 * Represents a join definition for model relationships.
 *
 * @package Proto\Models
 */
class ModelJoin
{
	/**
	 * Type of join.
	 *
	 * @var string
	 */
	protected string $type = 'JOIN';

	/**
	 * USING clause for join.
	 *
	 * @var string|null
	 */
	protected ?string $using = null;

	/**
	 * ON conditions for join.
	 *
	 * @var array
	 */
	protected array $on = [];

	/**
	 * Fields included in join.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Alias designation.
	 *
	 * @var string|null
	 */
	protected ?string $as = null;

	/**
	 * Join table name.
	 *
	 * @var string|array
	 */
	protected string|array $joinTableName;

	/**
	 * Alias for the join table.
	 *
	 * @var string|null
	 */
	protected ?string $joinAlias;

	/**
	 * Indicates if the join is multiple.
	 *
	 * @var bool
	 */
	protected bool $multiple = false;

	/**
	 * Holds the multiple join instance if applicable.
	 *
	 * @var ModelJoin|null
	 */
	protected ?ModelJoin $multipleJoin = null;

	/**
	 * ModelJoin constructor.
	 *
	 * @param JoinBuilder $builder Reference to join builder.
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @param bool $isSnakeCase Indicates snake_case usage.
	 */
	public function __construct(
		protected JoinBuilder $builder,
		protected string|array $tableName,
		protected ?string $alias = null,
		protected bool $isSnakeCase = true
	)
	{
		$this->setupJoinSettings();
	}

	/**
	 * Setup join settings from the builder.
	 *
	 * @return void
	 */
	protected function setupJoinSettings(): void
	{
		$joinSettings = $this->builder->getTableSettings();
		$this->joinTableName = $joinSettings->tableName;
		$this->joinAlias = $joinSettings->alias;
	}

	/**
	 * Override join table reference.
	 *
	 * @param string|array $tableName New table name.
	 * @param string|null $alias New alias.
	 * @return void
	 */
	protected function references(string|array $tableName, ?string $alias = null): void
	{
		$this->joinTableName = $tableName;
		$this->joinAlias = $alias;
	}

	/**
	 * Get the base table name.
	 *
	 * @return string|array
	 */
	public function getTableName(): string|array
	{
		return $this->tableName;
	}

	/**
	 * Get the table alias.
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}

	/**
	 * Set join as multiple.
	 *
	 * @param string|array|null $tableName Optional table name.
	 * @param string|null $alias Optional alias.
	 * @return self
	 */
	public function multiple(string|array $tableName = null, ?string $alias = null): self
	{
		$this->multiple = true;
		if (empty($tableName))
		{
			return $this;
		}
		$join = new ModelJoin($this->builder, $tableName, $alias);
		$join->references($this->tableName, $this->alias);
		$this->multipleJoin = $join;
		return $this;
	}

	/**
	 * Retrieve the multiple join instance.
	 *
	 * @return ModelJoin|null
	 */
	public function getMultipleJoin(): ?ModelJoin
	{
		return $this->multipleJoin;
	}

	/**
	 * Check if the join is multiple.
	 *
	 * @return bool
	 */
	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * Set the join type.
	 *
	 * @param string $type Join type.
	 * @return self
	 */
	public function addType(string $type = 'JOIN'): self
	{
		$this->type = strtoupper($type);
		return $this;
	}

	/**
	 * Get the join type.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Configure a left join.
	 *
	 * @return self
	 */
	public function left(): self
	{
		return $this->addType('LEFT JOIN');
	}

	/**
	 * Configure a right join.
	 *
	 * @return self
	 */
	public function right(): self
	{
		return $this->addType('RIGHT JOIN');
	}

	/**
	 * Configure an outer join.
	 *
	 * @return self
	 */
	public function outer(): self
	{
		return $this->addType('OUTER JOIN');
	}

	/**
	 * Configure a cross join.
	 *
	 * @return self
	 */
	public function cross(): self
	{
		return $this->addType('CROSS JOIN');
	}

	/**
	 * Set the alias designation.
	 *
	 * @param string $as Alias designation.
	 * @return self
	 */
	public function as(string $as): self
	{
		$this->as = $as;
		return $this;
	}

	/**
	 * Get the alias designation.
	 *
	 * @return string|array
	 */
	public function getAs(): string|array
	{
		return $this->as ?? $this->tableName;
	}

	/**
	 * Create a new join builder for this join.
	 *
	 * @param string|null $modelClassName Optional model class name.
	 * @return JoinBuilder
	 */
	public function join(?string $modelClassName = null): JoinBuilder
	{
		$builder = $this->builder->link($this->tableName, $this->alias);
		if ($modelClassName !== null)
		{
			$builder->setModelClassName($modelClassName);
		}
		return $builder;
	}

	/**
	 * Add fields to the join.
	 *
	 * @param string|array ...$fields Field names.
	 * @return self
	 */
	public function fields(string|array ...$fields): self
	{
		if (count($fields) < 1)
		{
			return $this;
		}

		foreach ($fields as $field)
		{
			$this->fields[] = $field;
		}
		return $this;
	}

	/**
	 * Get the join fields.
	 *
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * Set the USING clause.
	 *
	 * @param string $field Field name.
	 * @return self
	 */
	public function using(string $field): self
	{
		$this->using = 'USING(' . $field . ')';
		return $this;
	}

	/**
	 * Get the USING clause.
	 *
	 * @return string|null
	 */
	public function getUsing(): ?string
	{
		return $this->using;
	}

	/**
	 * Get ON conditions.
	 *
	 * @return array
	 */
	public function getOn(): array
	{
		return $this->on;
	}

	/**
	 * Convert a camelCase string to snake_case.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected static function decamelize(string $field): string
	{
		return Strings::snakeCase($field);
	}

	/**
	 * Prepare a column name for ON clause.
	 *
	 * @param string $column Column name.
	 * @return string
	 */
	protected function prepareOnColumn(string $column): string
	{
		return $this->isSnakeCase ? self::decamelize($column) : $column;
	}

	/**
	 * Add ON conditions.
	 *
	 * @param mixed ...$on ON conditions.
	 * @return self
	 */
	public function on(mixed ...$on): self
	{
		if (count($on) < 1)
		{
			return $this;
		}

		$baseAlias = $this->alias ?? $this->tableName;
		$joinAlias = $this->joinAlias ?? $this->joinTableName;
		$this->on = [];
		foreach ($on as $condition)
		{
			if (is_array($condition))
			{
				$count = count($condition);
				if ($count > 1)
				{
					if ($count === 2)
					{
						$condition = [
							$joinAlias . '.' . $this->prepareOnColumn($condition[0]),
							$baseAlias . '.' . $this->prepareOnColumn($condition[1])
						];
					}
					else
					{
						$condition = [
							$joinAlias . '.' . $this->prepareOnColumn($condition[0]),
							$condition[1],
							$baseAlias . '.' . $this->prepareOnColumn($condition[2])
						];
					}
				}
			}
			$this->on[] = $condition;
		}
		return $this;
	}
}