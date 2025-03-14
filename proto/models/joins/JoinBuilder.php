<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;

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
	 * Model class name for join.
	 *
	 * @var string|null
	 */
	protected ?string $modelClassName = null;

	/**
	 * JoinBuilder constructor.
	 *
	 * @param array $joins Reference to joins array.
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @param bool $isSnakeCase Indicates snake_case usage.
	 */
	public function __construct(
		protected array &$joins,
		protected string|array $tableName,
		protected ?string $alias = null,
		protected bool $isSnakeCase = true
	)
	{
	}

	/**
	 * Returns the table settings as an object.
	 *
	 * @return object
	 */
	public function getTableSettings(): object
	{
		return (object)[
			'tableName' => $this->tableName,
			'alias' => $this->alias,
		];
	}

	/**
	 * Sets the model class name for the join.
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
	 * Gets the model class name for the join.
	 *
	 * @return string
	 */
	public function getModelClassName(): string
	{
		return $this->modelClassName;
	}

	/**
	 * Gets the model class name for the join.
	 *
	 * @return string
	 */
	public function getModelRefName(): string
	{
		$modelClass = $this->getModelClassName();
		$modelName = $modelClass::getIdClassName();
		return Strings::snakeCase($modelName);
	}

	/**
	 * Gets the model identifier name (appending "Id" to the model class name).
	 *
	 * @return string
	 */
	public function getModelIdName(): string
	{
		$modelClass = $this->getModelClassName();
		$modelName = $modelClass::getIdClassName();
		return "{$modelName}Id";
	}

	/**
	 * Creates and adds a new join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function addJoin(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = new ModelJoin($this, $tableName, $alias, $this->isSnakeCase);
		$this->joins[] = $join;
		return $join;
	}

	/**
	 * Creates a generic join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function join(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias);
	}

	/**
	 * This will create a many join.
	 *
	 * @param string $modelName Model class name.
	 * @param string $type Join type (default is 'left').
	 * @return ModelJoin
	 */
	public function many(string $modelName, string $type = 'left'): ModelJoin
	{
		$this->setModelClassName($modelName);

		$tableName = $modelName::table();
		$alias = $modelName::alias();

		$join = $this->getJoinByType($type, $tableName, $alias);
		$join->multiple();
		return $join;
	}

	/**
	 * This will create a one to one join.
	 *
	 * @param string $modelName
	 * @param string $type
	 * @return ModelJoin
	 */
	public function one(string $modelName, string $type = 'left'): ModelJoin
	{
		$tableName = $modelName::table();
		$alias = $modelName::alias();

		return $this->getJoinByType($type, $tableName, $alias);
	}

	/**
	 * This will create a one join.
	 *
	 * @param string $type Join type.
	 * @param string $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function getJoinByType(string $type, string $tableName, string $alias = null): ModelJoin
	{
		$join = null;
		if ($type === 'right')
		{
			$join = $this->right($tableName, $alias);
		}
		else if ($type === 'outer')
		{
			$join = $this->outer($tableName, $alias);
		}
		else if ($type === 'cross')
		{
			$join = $this->cross($tableName, $alias);
		}
		else
		{
			$join = $this->left($tableName, $alias);
		}

		/**
		 * This will set the default on condition for the join.
		 */
		$modelRefName = $this->getModelRefName();
		$join->on(['id', $modelRefName . 'Id']);

		return $join;
	}

	/**
	 * Creates a left join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function left(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->left();
	}

	/**
	 * Creates a right join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function right(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->right();
	}

	/**
	 * Creates an outer join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function outer(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->outer();
	}

	/**
	 * Creates a cross join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function cross(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->cross();
	}

	/**
	 * Creates a linked join builder for further chaining.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return JoinBuilder
	 */
	public function link(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		return new JoinBuilder($this->joins, $tableName, $alias, $this->isSnakeCase);
	}
}