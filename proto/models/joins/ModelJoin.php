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
	 * @var string
	 */
	protected string $type = 'JOIN';

	/**
	 * USING clause for join.
	 * @var string|null
	 */
	protected ?string $using = null;

	/**
	 * ON conditions for join.
	 * @var array
	 */
	protected array $on = [];

	/**
	 * Fields included in join.
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Alias designation for the results of this join.
	 * @var string|null
	 */
	protected ?string $as = null;

	/**
	 * The table name this join connects *to*.
	 * @var string|array
	 */
	protected string|array $joinTableName;

	/**
	 * Alias for the table this join connects *to*.
	 * @var string|null
	 */
	protected ?string $joinAlias;

	/**
	 * Indicates if the join represents a one-to-many relationship target.
	 * @var bool
	 */
	protected bool $multiple = false;

	/**
	 * Holds the subsequent join instance when 'multiple' is used for chaining.
	 * @var ModelJoin|null
	 */
	protected ?ModelJoin $multipleJoin = null;

	/**
	 * ModelJoin constructor.
	 *
	 * @param JoinBuilder $builder Reference to join builder.
	 * @param string|array $tableName The base table name *for this specific join operation*.
	 * @param string|null $alias The alias *for this specific join operation's base table*.
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
	 * Setup join settings based on the builder's context at creation time.
	 * This defines the table the join connects *to*.
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
	 * Get the table name this join connects *to*.
	 *
	 * @return string|array
	 */
	public function getJoinTableName(): string|array
	{
		return $this->joinTableName;
	}

	/**
	 * Get the alias for the table this join connects *to*.
	 *
	 * @return string|null
	 */
	public function getJoinAlias(): ?string
	{
		return $this->joinAlias;
	}

	/**
	 * Internal method to redirect the reference table for a 'multiple' join.
	 *
	 * @param string|array $tableName New table name to reference.
	 * @param string|null $alias New alias to reference.
	 * @return void
	 */
	protected function references(string|array $tableName, ?string $alias = null): void
	{
		$this->joinTableName = $tableName;
		$this->joinAlias = $alias;
	}

	/**
	 * Get the base table name for this join operation.
	 *
	 * @return string|array
	 */
	public function getTableName(): string|array
	{
		return $this->tableName;
	}

	/**
	 * Get the alias for the base table of this join operation.
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}

	/**
	 * Mark the join as representing a multiple relationship target,
	 * optionally setting up a subsequent join definition.
	 *
	 * @param string|array|null $tableName Optional table name for a subsequent join.
	 * @param string|null $alias Optional alias for a subsequent join.
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
		$this->setMultipleJoin($join);
		return $this;
	}

	/**
	 * Set the subsequent join instance for a 'multiple' relationship chain.
	 * Adjusts the subsequent join to reference the base table of the current join.
	 *
	 * @param ModelJoin $join The join instance to set as the next step.
	 * @return void
	 */
	public function setMultipleJoin(ModelJoin $join): void
	{
		$this->multipleJoin = $join;
		$join->references($this->tableName, $this->alias);
	}

	/**
	 * Retrieve the subsequent join instance in a 'multiple' chain.
	 *
	 * @return ModelJoin|null
	 */
	public function getMultipleJoin(): ?ModelJoin
	{
		return $this->multipleJoin;
	}

	/**
	 * Check if the join is marked as multiple.
	 *
	 * @return bool
	 */
	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * Set the join type (e.g., JOIN, LEFT JOIN).
	 *
	 * @param string $type Join type string.
	 * @return self
	 */
	public function addType(string $type = 'JOIN'): self
	{
		$this->type = strtoupper($type);
		return $this;
	}

	/**
	 * Get the join type string.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Configure as a LEFT JOIN.
	 *
	 * @return self
	 */
	public function left(): self
	{
		return $this->addType('LEFT JOIN');
	}

	/**
	 * Configure as a RIGHT JOIN.
	 *
	 * @return self
	 */
	public function right(): self
	{
		return $this->addType('RIGHT JOIN');
	}

	/**
	 * Configure as an OUTER JOIN.
	 *
	 * @return self
	 */
	public function outer(): self
	{
		return $this->addType('OUTER JOIN');
	}

	/**
	 * Configure as a CROSS JOIN.
	 *
	 * @return self
	 */
	public function cross(): self
	{
		return $this->addType('CROSS JOIN');
	}

	/**
	 * Set the alias designation for the results of this join relationship.
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
	 * Get the alias designation for the results, defaulting to the base table name.
	 *
	 * @return string|array
	 */
	public function getAs(): string|array
	{
		return $this->as ?? $this->tableName;
	}

	/**
	 * Create a new linked join builder, continuing from this join's context.
	 *
	 * @param string|null $modelClassName Optional model class name to set the foreign key context.
	 * @return JoinBuilder
	 */
	public function join(?string $modelClassName = null): JoinBuilder
	{
		$builder = $this->builder->link($this->tableName, $this->alias);
		if ($modelClassName !== null)
		{
			$builder->setForeignKeyByModel($modelClassName);
		}
		return $builder;
	}

	/**
	 * Create a new independent join builder related to this join's context.
	 *
	 * @param string|null $modelClassName Optional model class name to set the foreign key context.
	 * @return JoinBuilder
	 */
	public function childJoin(?string $modelClassName = null): JoinBuilder
	{
		$builder = $this->builder->create($this->tableName, $this->alias);
		if ($modelClassName !== null)
		{
			$builder->setForeignKeyByModel($modelClassName);
		}
		return $builder;
	}

	/**
	 * Define a bridge table join (typically for many-to-many through).
	 * Note: Calls static method on the provided model class.
	 *
	 * @param string $modelClass The final target model class.
	 * @param string $type Join type for the bridge connection.
	 * @return ModelJoin Returns the result of the static call, likely a ModelJoin.
	 */
	public function bridge(string $modelClass, string $type = 'left'): ModelJoin
	{
		$builder = $this->join($modelClass);
		return $modelClass::many($builder, $type);
	}

	/**
	 * Define a 'many' relationship join originating from this join's context.
	 * Used for setting up the second part of a many-to-many or a one-to-many.
	 *
	 * @param string $modelClass The target model class for the 'many' side.
	 * @param string $type Join type.
	 * @return ModelJoin Returns the newly created ModelJoin representing the 'many' side.
	 */
	public function many(string $modelClass, string $type = 'left'): ModelJoin
	{
		$builder = $this->join($modelClass);
		$modelJoin = $this->createChildModelJoin($builder, $modelClass, $type);
		$this->setMultipleJoin($modelJoin);
		return $modelJoin;
	}

	/**
	 * Creates a ModelJoin instance representing a child relationship join.
	 *
	 * @param JoinBuilder $builder The builder context (usually linked or created).
	 * @param string $modelClassName The target model class name.
	 * @param string $type The desired join type ('left', 'right', etc.).
	 * @return ModelJoin
	 */
	protected function createChildModelJoin(JoinBuilder $builder, string $modelClassName, string $type = 'left'): ModelJoin
	{
		$join = $builder->createJoin($modelClassName::table(), $modelClassName::alias());

		// Set join type using fluent methods
		$methodName = strtolower($type);
		$join->$methodName();

		// Add the default ON clause based on the builder's foreign key context
		$builder->setDefaultOn($join);

		return $join;
	}


	/**
	 * Add fields to be selected from this join.
	 *
	 * @param string|array ...$fields Field names or arrays for aliasing.
	 * @return self
	 */
	public function fields(string|array ...$fields): self
	{
		if (count($fields) < 1)
		{
			return $this;
		}

		$this->fields = array_merge($this->fields, $fields);
		return $this;
	}

	/**
	 * Get the fields configured for this join.
	 *
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * Set the USING clause (alternative to ON for matching column names).
	 *
	 * @param string $field Field name for USING clause.
	 * @return self
	 */
	public function using(string $field): self
	{
		$this->using = 'USING(' . $field . ')';
		// Clear ON conditions if USING is set, as they are mutually exclusive
		$this->on = [];
		return $this;
	}

	/**
	 * Get the USING clause string.
	 *
	 * @return string|null
	 */
	public function getUsing(): ?string
	{
		return $this->using;
	}

	/**
	 * Get ON conditions configured for this join.
	 *
	 * @return array
	 */
	public function getOn(): array
	{
		return $this->on;
	}

	/**
	 * Prepare a column name for ON clause.
	 *
	 * @param string $column Column name.
	 * @return string
	 */
	protected function prepareOnColumn(string $column): string
	{
		return $this->isSnakeCase ? Strings::snakeCase($column) : $column;
	}

	/**
	 * Add ON conditions.
	 *
	 * @param mixed ...$on ON conditions.
	 * @return self
	 */
	public function on(...$on): self
	{
		if (count($on) < 1)
		{
			return $this;
		}

		$alias = $this->alias ?? $this->tableName;
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
						$condition = [$joinAlias.'.'.$this->prepareOnColumn($condition[0]), $alias.'.'.$this->prepareOnColumn($condition[1])];
					}
					else
					{
						$condition = [$joinAlias.'.'.$this->prepareOnColumn($condition[0]), $condition[1], $alias.'.'.$this->prepareOnColumn($condition[2])];
					}
				}
			}
			$this->on[] = $condition;
		}
		return $this;
	}
}