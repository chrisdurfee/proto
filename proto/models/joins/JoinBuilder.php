<?php declare(strict_types=1);
namespace Proto\Models\Joins;

// use Proto\Models\Joins\ModelJoin; // Only if JoinBuilder needs direct access
use Proto\Models\Joins\JoinFactory;
use Proto\Models\Joins\ModelNamingConvention;

/**
 * Class JoinBuilder
 *
 * Provides context for building joins and access to join creation methods.
 * Manages a collection of joins defined for a query.
 *
 * @package Proto\Models
 */
class JoinBuilder
{
	/**
	 * @var string|array The base table name or [db, table] for the current context.
	 */
	protected string|array $tableName;

	/**
	 * @var string|null The alias for the base table in the current context.
	 */
	protected ?string $alias;

	/**
	 * @var bool Indicates if snake_case should be used for derived names.
	 */
	protected bool $isSnakeCase;

	/**
	 * @var ModelNamingHelper Utility for deriving model-based names.
	 */
	protected ModelNamingHelper $namingConvention;

	/**
	 * @var JoinFactory Factory responsible for creating ModelJoin instances.
	 */
	protected JoinFactory $joinFactory;

	/**
	 * JoinBuilder constructor.
	 *
	 * @param array &$joins Reference to joins array.
	 * @param string|array $tableName Base table name for this builder context.
	 * @param string|null $alias Table alias for this builder context.
	 * @param bool $isSnakeCase Indicates snake_case usage.
	 */
	public function __construct(
		protected array &$joins,
		string|array $tableName,
		?string $alias = null,
		bool $isSnakeCase = true
	)
	{
		$this->joins =& $joins; // Keep the reference
		$this->tableName = $tableName;
		$this->alias = $alias;
		$this->isSnakeCase = $isSnakeCase;

		$this->namingConvention = new ModelNamingHelper($this->isSnakeCase);
		$this->joinFactory = new JoinFactory(
			$this->joins,
			$this,
			$this->namingConvention,
			$this->isSnakeCase
		);
	}

	/**
	 * Returns the table settings (name and alias) for the current context.
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
	 * Access the factory to create a generic join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function join(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->joinFactory->join($tableName, $alias);
	}

	/**
	 * Access the factory to create a left join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function left(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->joinFactory->left($tableName, $alias);
	}

	/**
	 * Access the factory to create a right join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function right(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->joinFactory->right($tableName, $alias);
	}

	/**
	 * Access the factory to create an outer join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function outer(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->joinFactory->outer($tableName, $alias);
	}

	/**
	 * Access the factory to create a cross join.
	 *
	 * @param string|array $tableName Table name or [db, table].
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function cross(string|array $tableName, ?string $alias = null): ModelJoin
	{
		return $this->joinFactory->cross($tableName, $alias);
	}

	/**
	 * Access the factory to create a one-to-one/many-to-one relationship join.
	 *
	 * @param string $modelName Related model class name.
	 * @param string $type Join type (default 'left').
	 * @return ModelJoin
	 */
	public function one(string $modelName, string $type = 'left'): ModelJoin
	{
		return $this->joinFactory->one($modelName, $type);
	}

	/**
	 * Access the factory to create a one-to-many relationship join.
	 *
	 * @param string $modelName Related model class name.
	 * @param string $type Join type (default 'left').
	 * @return ModelJoin
	 */
	public function many(string $modelName, string $type = 'left'): ModelJoin
	{
		return $this->joinFactory->many($modelName, $type);
	}

	/**
	 * Creates a linked join builder for further chaining, using a new table context
	 * but sharing the same underlying joins collection.
	 *
	 * @param string|array $tableName New base table name for the linked builder.
	 * @param string|null $alias New table alias for the linked builder.
	 * @return JoinBuilder
	 */
	public function link(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		return new self($this->joins, $tableName, $alias, $this->isSnakeCase);
	}

	/**
	 * Creates a completely new, independent join builder instance
	 * starting with an empty joins collection.
	 *
	 * @param string|array $tableName Base table name for the new builder.
	 * @param string|null $alias Table alias for the new builder.
	 * @return JoinBuilder
	 */
	public function create(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		$newJoins = [];
		return new self($newJoins, $tableName, $alias, $this->isSnakeCase);
	}

	/**
	 * Gets the collection of joins built so far.
	 * Useful if the calling code needs the final array.
	 *
	 * @return array
	 */
	public function getJoins(): array
	{
		return $this->joins;
	}
}