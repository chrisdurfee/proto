<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;
use Proto\Models\Model;

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
	 * @var string|null
	 */
	protected ?string $foreignKey = null;

	/**
	 * JoinBuilder constructor.
	 *
	 * @param array $joins Reference to joins array.
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @param bool $isSnakeCase Indicates snake_case usage.
	 * @param string|null $ownerModelClass Optional owner model class for context.
	 */
	public function __construct(
		protected array &$joins,
		protected string|array $tableName,
		protected ?string $alias = null,
		protected bool $isSnakeCase = true,
		protected ?string $ownerModelClass = null,
		protected ?Model $parentModel = null
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
	 * Gets the owner model class for the join.
	 *
	 * @return string|null
	 */
	public function getOwnerModelClass(): ?string
	{
		return $this->ownerModelClass;
	}

	/**
	 * Creates a foreign key name for the join based on convention.
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
	 * Sets the foreign key name for the join.
	 *
	 * @param string $foreignKey
	 * @return void
	 */
	public function setForeignKey(string $foreignKey): void
	{
		$this->foreignKey = $this->createForeignKeyId($foreignKey);
	}

	/**
	 * Sets the foreign key name for the join based on a model class.
	 *
	 * @param string $modelClass
	 * @return void
	 */
	public function setForeignKeyByModel(string $modelClass): void
	{
		$foreignKey = $this->getModelForeignKey($modelClass);
		$this->setForeignKey($foreignKey);
		$this->ownerModelClass = $modelClass;
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
	 * Creates a new join object.
	 *
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	public function createJoin(string|array $tableName, ?string $alias = null): ModelJoin
	{
		$modelJoin = new ModelJoin($this, $tableName, $alias, $this->isSnakeCase);
		if ($this->foreignKey !== null)
		{
			$this->setDefaultOn($modelJoin);
		}
		return $modelJoin;
	}

	/**
	 * Creates and adds a new join to the internal collection.
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
	 * Creates a generic join (defaults typically to INNER or LEFT depending on ModelJoin).
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
	 * Sets the default ON condition for the join if a foreign key is set.
	 *
	 * @param ModelJoin $join The join object to modify.
	 * @return void
	 */
	public function setDefaultOn(ModelJoin $join): void
	{
		$foreignKey = $this->getForeignKeyId();
		// Assumes the base table's primary key is 'id'
		$join->on(['id', $foreignKey]);
	}

	/**
	 * Creates and adds a join of a specific type.
	 *
	 * @param string $type The join type ('left', 'right', 'outer', 'cross').
	 * @param string|array $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	private function addTypedJoin(string $type, string|array $tableName, ?string $alias = null): ModelJoin
	{
		$join = $this->addJoin($tableName, $alias);
		return $join->$type();
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
		return $this->addTypedJoin('left', $tableName, $alias);
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
		return $this->addTypedJoin('right', $tableName, $alias);
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
		return $this->addTypedJoin('outer', $tableName, $alias);
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
		return $this->addTypedJoin('cross', $tableName, $alias);
	}

	/**
	 * Gets a join object configured for a specific type.
	 *
	 * @param string $type Join type.
	 * @param string $tableName Base table name.
	 * @param string|null $alias Table alias.
	 * @return ModelJoin
	 */
	protected function getJoinByType(string $type, string $tableName, string $alias = null): ModelJoin
	{
		return match (strtolower($type))
		{
			'right' => $this->right($tableName, $alias),
			'outer' => $this->outer($tableName, $alias),
			'cross' => $this->cross($tableName, $alias),
			default => $this->left($tableName, $alias), // Default to left
		};
	}

	/**
	 * Creates a join based on a model class, specifying relationship cardinality.
	 *
	 * @param string $modelName Model class name.
	 * @param string $type Join type (default is 'left').
	 * @param bool $isMultiple True for a 'many' relationship, false for 'one'.
	 * @return ModelJoin
	 */
	private function createModelJoin(string $modelName, string $type = 'left', bool $isMultiple = false): ModelJoin
	{
		$tableName = $modelName::table();
		$alias = $modelName::alias();

		$join = $this->getJoinByType($type, $tableName, $alias);

		if ($isMultiple)
		{
			$join->multiple();
		}

		return $join;
	}

	/**
	 * Creates a one-to-one or one-to-many (where this model is the 'one') join based on a model definition.
	 *
	 * @param string $modelName The related model class name.
	 * @param string $type Join type (default is 'left').
	 * @param array $fields Optional fields to select from the related model.
	 * @return ModelJoin
	 */
	public function one(string $modelName, string $type = 'left', array $fields = []): ModelJoin
	{
		$join = $this->createModelJoin($modelName, $type, false);

		if (count($fields))
		{
			$join->fields(...$fields);
		}

		return $join;
	}

	/**
	 * Creates a one-to-many (where this model is the 'many') or many-to-many join based on a model definition.
	 *
	 * @param string $modelName The related model class name.
	 * @param string $type Join type (default is 'left').
	 * @param array $fields Optional fields to select from the related model.
	 * @return ModelJoin
	 */
	public function many(string $modelName, string $type = 'left', array $fields = []): ModelJoin
	{
		$join = $this->createModelJoin($modelName, $type, true);

		if (count($fields))
		{
			$join->fields(...$fields);
		}

		return $join;
	}

	/**
	 * Creates a linked join builder for further chaining relative to a new table context.
	 * Shares the same underlying joins collection.
	 *
	 * @param string|array $tableName Base table name for the linked context.
	 * @param string|null $alias Table alias for the linked context.
	 * @return JoinBuilder
	 */
	public function link(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		return new JoinBuilder(
			$this->joins,
			$tableName,
			$alias,
			$this->isSnakeCase,
			$this->ownerModelClass,
			$this->parentModel
		);
	}

	/**
	 * Creates a completely new, independent join builder instance.
	 * Does not share the joins collection with the current instance.
	 *
	 * @param string|array $tableName Base table name for the new builder.
	 * @param string|null $alias Table alias for the new builder.
	 * @return JoinBuilder
	 */
	public function create(string|array $tableName, ?string $alias = null): JoinBuilder
	{
		$joins = [];
		return new JoinBuilder(
			$joins,
			$tableName,
			$alias,
			$this->isSnakeCase,
			$this->ownerModelClass,
			$this->parentModel
		);
	}

	/**
	 * Creates a new join for a belongs-to-many relationship.
	 *
	 * This is typically used for many-to-many relationships where the pivot table
	 * is required to link two models.
	 *
	 * @param string $related The related model class name.
	 * @param array $relatedFields Fields to select from the related model.
	 * @param array $pivotFields Fields to select from the pivot table.
	 * @return ModelJoin
	 */
	public function belongsToMany(string $related, array $relatedFields = [], array $pivotFields = []): ModelJoin
	{
		if (isset($this->parentModel))
		{
			$key = BelongsToHelper::inferOtherBase($related) . 's';
			$this->parentModel->addRelation($key, $related);
		}

		return BelongsToHelper::createBelongsToMany(
			$this,
			$related,
			$relatedFields,
			$pivotFields
		);
	}
}