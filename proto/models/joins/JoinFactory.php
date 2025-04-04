<?php declare(strict_types=1);
namespace Proto\Models\Joins;

// Assuming ModelJoin class exists and has the necessary methods
// like on(), multiple(), left(), right(), outer(), cross()
// use Proto\Models\Joins\ModelJoin;

/**
 * Class JoinFactory
 *
 * Responsible for creating and configuring different types of joins,
 * including model-based relationship joins.
 *
 * @package Proto\Models\Joins
 */
class JoinFactory
{
	/**
	 * JoinFactory constructor.
	 *
	 * @param array &$joins Reference to the joins collection.
	 * @param JoinBuilder $contextBuilder The builder providing the current table context.
	 * @param ModelNamingHelper $namingConvention Naming convention helper.
	 * @param bool $isSnakeCase Snake case setting.
	 */
	public function __construct(
		protected array &$joins,
		protected JoinBuilder $contextBuilder,
		protected ModelNamingHelper $namingConvention,
		protected bool $isSnakeCase
	)
	{
		$this->joins =& $joins;
		$this->contextBuilder = $contextBuilder;
		$this->namingConvention = $namingConvention;
		$this->isSnakeCase = $isSnakeCase;
	}

	/**
	 * Creates a new ModelJoin instance but does not add it to the collection yet.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function createModelJoin(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return new ModelJoin($this->contextBuilder, $tableName, $alias, $this->isSnakeCase);
	}

	/**
	 * Creates a ModelJoin, adds it to the collection, and returns it.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function addJoin(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->createModelJoin($tableName, $alias);
		$this->joins[] = $join;
		return $join;
	}

	/**
	 * Creates a generic join (defaults to INNER or type set later).
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function join(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias);
	}

	/**
	 * Creates a left join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function left(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias)->left();
	}

	/**
	 * Creates a right join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function right(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias)->right();
	}

	/**
	 * Creates an outer join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function outer(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias)->outer();
	}

	/**
	 * Creates a cross join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function cross(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->addJoin($tableName, $alias)->cross();
	}

	/**
	 * Creates a relationship join based on model conventions.
	 *
	 * @param string $modelClassName The related model class name.
	 * @param string $type The type of join ('left', 'right', 'outer', 'cross', 'inner').
	 * @param bool $isMultiple Indicates if it's a one-to-many relationship.
	 * @return ModelJoin
	 */
	protected function createRelationshipJoin(string $modelClassName, string $type, bool $isMultiple): ModelJoin
	{
		$tableName = $this->namingConvention->getTableName($modelClassName);
		$alias = $this->namingConvention->getTableAlias($modelClassName);

		$join = match (strtolower($type)) {
			'right' => $this->right($tableName, $alias),
			'outer' => $this->outer($tableName, $alias),
			'cross' => $this->cross($tableName, $alias),
			'inner' => $this->join($tableName, $alias),
			default => $this->left($tableName, $alias)
		};

		$foreignKey = $this->namingConvention->getForeignKeyName($modelClassName);
		$join->on(['id', $foreignKey]);

		if ($isMultiple)
		{
			$join->multiple();
		}

		return $join;
	}

	/**
	 * Creates a one-to-one or many-to-one relationship join.
	 *
	 * @param string $modelClassName Related model class name.
	 * @param string $type Join type (default 'left').
	 * @return ModelJoin
	 */
	public function one(string $modelClassName, string $type = 'left'): ModelJoin
	{
		return $this->createRelationshipJoin($modelClassName, $type, false);
	}

	/**
	 * Creates a one-to-many relationship join.
	 *
	 * @param string $modelClassName Related model class name.
	 * @param string $type Join type (default 'left').
	 * @return ModelJoin
	 */
	public function many(string $modelClassName, string $type = 'left'): ModelJoin
	{
		return $this->createRelationshipJoin($modelClassName, $type, true);
	}
}