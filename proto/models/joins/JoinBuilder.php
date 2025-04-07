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
	/**	 * @var string|null
	 */
	protected ?string $foreignKey = null;

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
			'foreignKey' => $this->foreignKey
		];
	}

	/**
	 * Gets the model class name for the join.
	 *
	 * @param string $modelClass
	 * @return string
	 */
	private function getModelForeignKey(string $modelClass): string
	{
		return $modelClass::getIdClassName();
	}

	/**
	 * This will create a foreign key name for the join.
	 *
	 * @param string $foreignKey
	 * @return string
	 */
	protected function createForeignKeyId(string $foreignKey): string
	{
		if (empty($foreignKey))
		{
			return $foreignKey;
		}

		return $this->isSnakeCase ? Strings::snakeCase($foreignKey) . '_id' : $foreignKey . 'Id';
	}

	/**
	 * This will set the foreign key name for the join.
	 *
	 * @param string $foreignKey
	 * @return void
	 */
	public function setForeignKey(string $foreignKey): void
	{
		$this->foreignKey = $this->createForeignKeyId($foreignKey);
	}

	/**
	 * This will set the forgeign key name for the join.
	 *
	 * @param string $foreignKey
	 * @return void
	 */
	public function setForeignKeyByModel(string $modelClass): void
	{

		$foreignKey = $this->getModelForeignKey($modelClass);
		$this->setForeignKey($foreignKey);
	}

	/**
	 * Gets the join foreign key id.
	 *
	 * @return string|null
	 */
	public function getForeignKeyId(): ?string
	{
		return $this->foreignKey;
	}

	/**
	 * Creates a new join.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function createJoin(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return new ModelJoin($this, $tableName, $alias, $this->isSnakeCase);
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
		$join = $this->createJoin($tableName, $alias);
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
	 * This will set the default on condition for the join.
	 *
	 * @param object $join
	 * @return void
	 */
	protected function setDefaultOn(object $join): void
	{
		$foreignKey = $this->getForeignKeyId();
		$join->on(['id', $foreignKey]);
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
		$this->setDefaultOn($join);

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

	/**
	 * Creates a new join builder for the specified table name and alias.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return JoinBuilder
	 */
	public function create(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		/**
		 * This will create a new join builder instance without any existing joins.
		 */
		$joins = [];
		return new JoinBuilder($joins, $tableName, $alias, $this->isSnakeCase);
	}
}