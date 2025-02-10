<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * Class JoinBuilder
 *
 * Builds join configurations for models.
 *
 * @package Proto\Models
 */
class JoinBuilder
{
	/**
	 * Base table name.
	 *
	 * @var string
	 */
	protected string $tableName;

	/**
	 * Table alias.
	 *
	 * @var string|null
	 */
	protected ?string $alias;

	/**
	 * Indicates snake_case usage.
	 *
	 * @var bool
	 */
	protected bool $isSnakeCase = true;

	/**
	 * Model class name for join.
	 *
	 * @var string|null
	 */
	protected ?string $modelClassName;

	/**
	 * Array of join definitions.
	 *
	 * @var array
	 */
	protected array $joins;

	/**
	 * JoinBuilder constructor.
	 *
	 * @param array $joins Reference to joins array.
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @param bool $isSnakeCase Indicates snake_case usage.
	 */
	public function __construct(array &$joins, mixed $tableName, ?string $alias = null, bool $isSnakeCase = true)
	{
		$this->tableName = $tableName;
		$this->joins = &$joins;
		$this->alias = $alias;
		$this->isSnakeCase = $isSnakeCase;
	}

	/**
	 * Get table settings.
	 *
	 * @return object
	 */
	public function getTableSettings(): object
	{
		return (object)[
			'tableName' => $this->tableName,
			'alias' => $this->alias
		];
	}

	/**
	 * Set the model class name for join.
	 *
	 * @param string $modelClassName
	 * @return self
	 */
	public function setModelClassName(string $modelClassName): self
	{
		$this->modelClassName = $modelClassName;
		return $this;
	}

	/**
	 * Get the model identifier name.
	 *
	 * @return string
	 */
	public function getModelIdName(): string
	{
		return "{$this->modelClassName}Id";
	}

	/**
	 * Add a new join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function addJoin(mixed $tableName, ?string $alias = null): ModelJoin
	{
		$join = new ModelJoin($this, $tableName, $alias, $this->isSnakeCase);
		$this->joins[] = $join;
		return $join;
	}

	/**
	 * Create a join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function join(mixed $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias);
	}

	/**
	 * Create a left join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function left(mixed $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->left();
	}

	/**
	 * Create a right join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function right(mixed $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->right();
	}

	/**
	 * Create an outer join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function outer(mixed $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->outer();
	}

	/**
	 * Create a cross join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function cross(mixed $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->cross();
	}

	/**
	 * Create a linked join builder.
	 *
	 * @param mixed $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return JoinBuilder
	 */
	public function link(mixed $tableName, ?string $alias = null): JoinBuilder
	{
		return new JoinBuilder($this->joins, $tableName, $alias, $this->isSnakeCase);
	}
}