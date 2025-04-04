<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;

/**
 * Class ModelJoin
 *
 * Represents a single join definition within a query.
 * Configured via JoinFactory and holds details like type, table, alias, and conditions.
 * Uses OnHelper to process ON conditions.
 *
 * @package Proto\Models
 */
class ModelJoin
{
	/**
	 * @var string
	 */
	protected string $type = 'JOIN';

	/**
	 * @var string|null
	 */
	protected ?string $usingColumn = null;

	/**
	 * ON conditions for join. Array of condition sets processed by OnHelper.
	 * Example: [['left' => 'table.col', 'op' => '=', 'right' => 'othertable.col'], 'raw SQL condition']
	 *
	 * @var array
	 */
	protected array $on = [];

	/**
	 * Fields to be selected from this joined table.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Alias for mapping results (optional, used during hydration/result processing).
	 *
	 * @var string|null
	 */
	protected ?string $as = null;

	/**
	 * Indicates if this join represents a one-to-many relationship.
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
	 * The table name of the context/base (the "left" side of the join).
	 * Populated from the context JoinBuilder.
	 *
	 * @var string|array
	 */
	protected string|array $contextTableName;

	/**
	 * The alias of the context/base table (the "left" side of the join).
	 * Populated from the context JoinBuilder.
	 *
	 * @var string|null
	 */
	protected ?string $contextAlias;

	/**
	 * ModelJoin constructor.
	 * Instantiated by JoinFactory.
	 *
	 * @param JoinBuilder $contextBuilder Reference to the builder providing the join context ("left" side).
	 * @param string|array $tableName Table name for *this* join (the "right" side).
	 * @param string|null $alias Alias for *this* join's table.
	 * @param bool $isSnakeCase Indicates snake_case usage for ON conditions processed by OnHelper.
	 */
	public function __construct(
		protected JoinBuilder $contextBuilder,
		protected string|array $tableName,
		protected ?string $alias = null,
		protected bool $isSnakeCase = true
	)
	{
		$this->setupContextSettings();
	}

	/**
	 * Set context table details from the context builder.
	 *
	 * @return void
	 */
	protected function setupContextSettings(): void
	{
		$contextSettings = $this->contextBuilder->getTableSettings();
		$this->contextTableName = $contextSettings->tableName;
		$this->contextAlias = $contextSettings->alias;
	}

	/**
	 * Get ON conditions. Returns array of structured arrays or raw strings.
	 *
	 * @return array
	 */
	public function getOn(): array
	{
		return $this->on;
	}

	/**
	 * Clears all previously set ON conditions.
	 * Also clears any USING clause as they are mutually exclusive.
	 *
	 * @return self
	 */
	public function clearOn(): self
	{
		$this->on = [];
		$this->usingColumn = null;
		return $this;
	}

	/**
	 * Adds ON conditions for the join (additive).
	 * Replaces any USING clause. Delegates processing to OnHelper.
	 *
	 * @param mixed ...$conditions Conditions to add.
	 * @return self
	 */
	public function on(mixed ...$conditions): self
	{
		if (count($conditions) < 1)
		{
			return $this;
		}
		$this->usingColumn = null;

		$thisAlias = $this->alias ?? $this->tableName;
		$contextAlias = $this->contextAlias ?? $this->contextTableName;

		// Instantiate the helper with the current join's context
		$helper = new OnHelper($thisAlias, $contextAlias, $this->isSnakeCase);

		foreach ($conditions as $condition)
		{
			$processed = $helper->process($condition);
			if ($processed !== null)
			{
				$this->on[] = $processed;
			}
		}

		return $this;
	}

	/**
	 * Sets the join type internally.
	 *
	 * @param string $type SQL join type (e.g., 'LEFT JOIN').
	 * @return self
	 */
	protected function setType(string $type = 'JOIN'): self
	{
		$this->type = strtoupper($type);
		return $this;
	}

	/**
	 * Configure as a LEFT JOIN.
	 *
	 * @return self
	 */
	public function left(): self
	{
		return $this->setType('LEFT JOIN');
	}

	/**
	 * Configure as a RIGHT JOIN.
	 *
	 * @return self
	 */
	public function right(): self
	{
		return $this->setType('RIGHT JOIN');
	}

	/**
	 * Configure as an OUTER JOIN (typically FULL OUTER).
	 *
	 * @return self
	 */
	public function outer(): self
	{
		return $this->setType('OUTER JOIN');
	}

	/**
	 * Configure as a CROSS JOIN.
	 *
	 * @return self
	 */
	public function cross(): self
	{
		return $this->setType('CROSS JOIN');
	}

	/**
	 * Configure as an INNER JOIN.
	 *
	 * @return self
	 */
	public function inner(): self
	{
		return $this->setType('INNER JOIN');
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

		$join = new ModelJoin($this->contextBuilder, $tableName, $alias);
		$this->setMultipleJoin($join);
		return $this;
	}

	/**
	 * Set the multiple join instance.
	 *
	 * @param ModelJoin $join The join instance to set.
	 * @return void
	 */
	public function setMultipleJoin(ModelJoin $join): void
	{
		$this->multipleJoin = $join;
		$join->references($this->tableName, $this->alias);
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
	 * Set the alias designation for result mapping.
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
	 * Add fields to be selected from this join. Appends unique fields.
	 *
	 * @param string|array ...$fields Field names. Can be simple strings or ['alias' => 'column'].
	 * @return self
	 */
	public function fields(string|array ...$fields): self
	{
		if (count($fields) < 1)
		{
			return $this;
		}

		$flatFields = [];
		foreach ($fields as $field)
		{
			if (!is_array($field))
			{
				$flatFields[] = (string)$field; // Ensure string
				continue;
			}

			if (array_is_list($field))
			{
				array_push($flatFields, ...$field);
			}
			else
			{
				array_push($flatFields, ...array_values($field));
			}
		}

		$this->fields = array_unique(array_merge($this->fields, $flatFields));
		return $this;
	}

	/**
	 * Set the USING clause (replaces any ON conditions).
	 *
	 * @param string $column Column name for the USING clause.
	 * @return self
	 */
	public function using(string $column): self
	{
		$this->usingColumn = 'USING(' . $column . ')';
		$this->on = [];
		return $this;
	}

	/**
	 * Get the alias designation for result mapping.
	 * Defaults to the join table alias or name if not set.
	 *
	 * @return string|array
	 */
	public function getAs(): string|array
	{
		return $this->as ?? $this->alias ?? $this->tableName;
	}

	/**
	 * Create a new join builder for this join.
	 *
	 * @return JoinBuilder
	 */
	public function join(): JoinBuilder
	{
		return $this->contextBuilder->link($this->tableName, $this->alias);
	}

	/**
	 * This will create a child join builder for the
	 * model class and the join table.
	 *
	 * @return JoinBuilder
	 */
	public function childJoin(): JoinBuilder
	{
		return $this->contextBuilder->create($this->tableName, $this->alias);
	}

	/**
	 * This will create a bridge table join for the
	 * model class and the bridge table.
	 *
	 * @param string $modelClass Model class
	 * @param string $type Join type
	 * @return ModelJoin
	 */
	public function bridge(string $modelClass, string $type = 'left'): ModelJoin
	{
		$builder = $this->childJoin();
		$modelJoin = $modelClass::bridge($builder, $type);
		$this->setMultipleJoin($modelJoin);
		return $modelJoin;
	}

	/**
	 * This will create a many table join for the
	 * model class and the bridge table.
	 *
	 * @param string $modelClass Model class
	 * @param string $type Join type
	 * @return ModelJoin
	 */
	public function many(string $modelClass, string $type = 'left'): ModelJoin
	{
		$builder = $this->childJoin();
		$modelJoin = $modelClass::many($builder, $type);
		$this->setMultipleJoin($modelJoin);
		return $modelJoin;
	}

	/**
	 * Get the fields specified for selection from this join.
	 *
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * Get the USING clause column name, if set.
	 *
	 * @return string|null
	 */
	public function getUsing(): ?string
	{
		return $this->usingColumn;
	}

	/**
	 * Get the table name being joined ("right" side).
	 *
	 * @return string|array
	 */
	public function getTableName(): string|array
	{
		return $this->tableName;
	}

	/**
	 * Get the alias for the table being joined ("right" side).
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}

	/**
	 * Get the context table name ("left" side).
	 *
	 * @return string|array
	 */
	public function getContextTableName(): string|array
	{
		return $this->contextTableName;
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
		$this->contextTableName = $tableName;
		$this->contextAlias = $alias;
	}

	/**
	 * Get the context table alias ("left" side).
	 *
	 * @return string|null
	 */
	public function getContextAlias(): ?string
	{
		return $this->contextAlias;
	}

	/**
	 * Get the join type (e.g., 'LEFT JOIN').
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Check if the join represents a one-to-many relationship.
	 *
	 * @return bool
	 */
	public function isMultiple(): bool
	{
		return $this->multiple;
	}
}