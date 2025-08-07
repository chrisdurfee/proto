<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Base;
use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Models\Data\Data;
use Proto\Storage\Storage;
use Proto\Storage\StorageProxy;
use Proto\Tests\Debug;
use Proto\Support\Collection;
use Proto\Utils\Strings;
use Proto\Models\Joins\JoinBuilder;
use Proto\Models\Joins\ModelJoin;
use Proto\Database\QueryBuilder\QueryHandler;

/**
 * Class Model
 *
 * Base model class for handling data persistence and mapping.
 *
 * @package Proto\Models
 * @abstract
 */
abstract class Model extends Base implements \JsonSerializable, ModelInterface
{
	/**
	 * Table name for the model.
	 *
	 * @var string|null
	 */
	protected static ?string $tableName = null;

	/**
	 * Alias for the model.
	 *
	 * @var string|null
	 */
	protected static ?string $alias = null;

	/**
	 * Identifier key name.
	 *
	 * @var string
	 */
	protected static string $idKeyName = 'id';

	/**
	 * Model fields.
	 *
	 * @var array
	 */
	protected static array $fields = [];

	/**
	 * Join definitions.
	 *
	 * @var array
	 */
	protected static array $joins = [];

	/**
	 * Compiled join definitions.
	 *
	 * @var array
	 */
	protected array $compiledJoins = [];

	/**
	 * Fields to exclude when exporting.
	 *
	 * @var array
	 */
	protected static array $fieldsBlacklist = [];

	/**
	 * Storage connection instance.
	 *
	 * @var StorageProxy|null
	 */
	public ?StorageProxy $storage = null;

	/**
	 * Storage wrapper instance.
	 *
	 * @var StorageWrapper|null
	 */
	public ?StorageWrapper $storageWrapper = null;

	/**
	 * Storage type for the model.
	 *
	 * @var string
	 */
	protected static string $storageType = Storage::class;

	/**
	 * Data mapper instance.
	 *
	 * @var Data
	 */
	protected Data $data;

	/**
	 * Indicates if the model data uses snake_case.
	 *
	 * @var bool
	 */
	protected static bool $isSnakeCase = true;

	/**
	 * The belongs to many eager loaded relations.
	 *
	 * @var array
	 */
	protected array $relations = [];

	/**
	 * Model constructor.
	 *
	 * @param object|null $data Data object to initialize the model.
	 */
	public function __construct(?object $data = null)
	{
		parent::__construct();
		$this->init($data);
	}

	/**
	 * Initialize model with data.
	 *
	 * @param object|null $data Data object.
	 * @return void
	 */
	protected function init(?object $data): void
	{
		$this->setupJoins();
		$this->setupDataMapper();
		$this->setupStorage();

		$data = static::augment($data);
		$this->data->set($data);
	}

	/**
	 * Set the storage type for the model.
	 *
	 * @param string $storageType
	 * @return void
	 */
	public function setStorageType(string $storageType): void
	{
		static::$storageType = $storageType;
		$this->setupStorage();
	}

	/**
	 * Get the identifier key name.
	 *
	 * @return string
	 */
	public static function idKeyName(): string
	{
		return (static::$isSnakeCase === true)
			? Strings::snakeCase(static::$idKeyName)
			: static::$idKeyName;
	}

	/**
	 * Get the identifier key name.
	 *
	 * @return string
	 */
	public function getIdKeyName(): string
	{
		return static::idKeyName();
	}

	/**
	 * Set the model identifier.
	 *
	 * @param mixed $value Identifier value.
	 * @return void
	 */
	public function setId(mixed $value): void
	{
		$key = $this->getIdKeyName();
		$this->set($key, $value);
	}

	/**
	 * Retrieve model joins.
	 *
	 * @return array<ModelJoin>
	 */
	protected function getModelJoins(): array
	{
		$joins = [];
		$alias = static::$alias ?? null;
		$builder = new JoinBuilder(
			$joins,
			static::$tableName,
			$alias,
			static::$isSnakeCase,
			static::class,
			$this
		);

		$modelClassName = static::class;
		$builder->setForeignKeyByModel($modelClassName);

		$callback = static::class . '::joins';
		\call_user_func($callback, $builder);
		return $joins;
	}

	/**
	 * Get the identifier class name.
	 *
	 * @return string
	 */
	public static function getIdClassName(): string
	{
		$className = (new \ReflectionClass(static::class))->getShortName();
		return Strings::lowercaseFirstChar($className);
	}

	/**
	 * Set up a bridge join.
	 *
	 * @param JoinBuilder $builder
	 * @param string $type Join type.
	 * @return ModelJoin
	 */
	public static function bridge(JoinBuilder $builder, string $type = 'left'): ModelJoin
	{
		return $builder->many(static::class, $type);
	}

	/**
	 * Set up a one-to-many join.
	 *
	 * @param JoinBuilder $builder
	 * @param string $type Join type.
	 * @return ModelJoin
	 */
	public static function many(JoinBuilder $builder, string $type = 'left'): ModelJoin
	{
		return $builder->many(static::class, $type);
	}

	/**
	 * Set up a one-to-one join.
	 *
	 * @param JoinBuilder $builder
	 * @param string $type Join type.
	 * @return ModelJoin
	 */
	public static function one(JoinBuilder $builder, string $type = 'left'): ModelJoin
	{
		return $builder->one(static::class, $type);
	}

	/**
	 * Adds a relation to the model.
	 *
	 * @param string $key
	 * @param string $relation
	 * @return void
	 */
	public function addRelation(string $key, string $relation): void
	{
		$this->relations[$key] = $relation;
	}

	/**
	 * Checks if a relation exists for the given key.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function isRelation(string $key): bool
	{
		return isset($this->relations[$key]);
	}

	/**
	 * Retrieves the relation for the given key.
	 *
	 * @param string $key
	 * @return string|null
	 */
	protected function getRelation(string $key): ?string
	{
		return $this->relations[$key] ?? null;
	}

	/**
	 * This will call the relation for the given key.
	 *
	 * @param string $key
	 * @return Relations\BelongsToMany|null
	 */
	protected function callRelation(string $key): ?Relations\BelongsToMany
	{
		$relation = $this->getRelation($key);
		if (!$relation)
		{
			return null;
		}

		return $this->belongsToMany($relation);
	}

	/**
	 * Set up model joins.
	 *
	 * @return void
	 */
	protected function setupJoins(): void
	{
		$joins = $this->getModelJoins();
		if (\count($joins) < 1)
		{
			return;
		}

		$this->compiledJoins = $joins;
	}

	/**
	 * Check if model data is snake_case.
	 *
	 * @return bool
	 */
	public function isSnakeCase(): bool
	{
		return static::$isSnakeCase;
	}

	/**
	 * Set up the data mapper.
	 *
	 * @return void
	 */
	protected function setupDataMapper(): void
	{
		$this->data = new Data(
			static::$fields,
			$this->compiledJoins,
			static::$fieldsBlacklist,
			static::$isSnakeCase
		);
	}

	/**
	 * Get the table name.
	 *
	 * @return string|null
	 */
	public static function table(): ?string
	{
		return static::$tableName;
	}

	/**
	 * Get the table name.
	 *
	 * @return string|null
	 */
	public function getTableName(): ?string
	{
		return static::$tableName;
	}

	/**
	 * Get the table alias.
	 *
	 * @return string|null
	 */
	public static function alias(): ?string
	{
		return static::$alias;
	}

	/**
	 * Get the alias.
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string
	{
		return static::$alias;
	}

	/**
	 * Set model data.
	 *
	 * @param mixed ...$args Data object or key-value pair.
	 * @return self
	 */
	public function set(...$args): self
	{
		$this->data->set(...$args);
		return $this;
	}

	/**
	 * Magic setter for model properties or relations.
	 *
	 * If a relation name matches, we store it in $relations instead of $data.
	 *
	 * @param string $key Property or relation name.
	 * @param mixed $value Value to assign.
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		if ($this->isRelationMethod($key))
		{
			$this->relations[$key] = $value;
			return;
		}

		// Otherwise treat as normal data field
		$this->set($key, $value);
	}

	/**
	 * Magic getter for model properties or lazy relations.
	 *
	 * 1. If $data has the key, return it.
	 * 2. Elseif a relationship method exists, load it now and stash into $data.
	 * 3. Otherwise, return null.
	 *
	 * @param string $key Property or relation name.
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		$camel = Strings::camelCase($key);

		// 1) If the mapper has it as a field, return it.
		if ($this->data->has($camel))
		{
			return $this->data->get($camel);
		}

		// 2) If a relationship method exists, call it and load results.
		if ($this->isRelationMethod($key))
		{
			$relation = $this->$key();
			$value = $relation->getResults();
			$this->data->addJoinField($key, $value);
			return $value;
		}

		// 3) Not found—return null.
		return null;
	}

	/**
	 * Tell PHP whether $model->foo should be considered "set"
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool
	{
		$camel = Strings::camelCase($key);
		return ($this->data->get($camel) !== null);
	}

	/**
	 * This will remove the specified value from the model's data.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		$this->data->unset(Strings::camelCase($key));
	}

	/**
	 * Determine if a given key corresponds to a relationship method.
	 *
	 * @param string $key Method name.
	 * @return bool True if the method exists and returns a Relation.
	 */
	protected function isRelationMethod(string $key): bool
	{
		if (!method_exists($this, $key))
		{
			return false;
		}

		$rf = new \ReflectionMethod($this, $key);
		return $rf->isPublic() && $rf->getNumberOfParameters() === 0;
	}

	/**
	 * Get the foreign key name for relationships.
	 *
	 * @param mixed $foreignKey
	 * @return string
	 */
	protected function getForeignKeyName(?string $foreignKey = null): string
	{
		return $foreignKey ?? Strings::snakeCase(static::getIdClassName()) . '_id';
	}

	/**
	 * Define a one-to-many (HasMany) relationship.
	 *
	 * @param string $related Related model class.
	 * @param string|null $foreignKey FK on related table (defaults to snake-case this model + "_id").
	 * @param string|null $localKey PK on this model (defaults to this model's key).
	 * @return Relations\HasMany
	 */
	public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasMany
	{
		$localKey = $localKey ?? $this->getIdKeyName();
		$foreignKey = $this->getForeignKeyName($foreignKey);
		return new Relations\HasMany($related, $foreignKey, $localKey, $this);
	}

	/**
	 * Define a one-to-one (HasOne) relationship.
	 *
	 * @param string $related Related model class.
	 * @param string|null $foreignKey FK on related table (defaults to snake-case this model + "_id").
	 * @param string|null $localKey PK on this model (defaults to this model's key).
	 * @return Relations\HasOne
	 */
	public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasOne
	{
		$localKey = $localKey ?? $this->getIdKeyName();
		$foreignKey = $this->getForeignKeyName($foreignKey);
		return new Relations\HasOne($related, $foreignKey, $localKey, $this);
	}

	/**
	 * Define an inverse one-to-many/one-to-one (BelongsTo) relationship.
	 *
	 * @param string $related Related model class.
	 * @param string|null $foreignKey FK on this table (defaults to snake-case related model + "_id").
	 * @param string|null $ownerKey PK on related model (defaults to related model's key).
	 * @return Relations\BelongsTo
	 */
	public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Relations\BelongsTo
	{
		$ownerKey = $ownerKey ?? $related::idKeyName();
		$foreignKey = $this->getForeignKeyName($foreignKey);
		return new Relations\BelongsTo($related, $foreignKey, $ownerKey, $this);
	}

	/**
	 * Define a many-to-many (BelongsToMany) relationship.
	 *
	 * @param string $related Related model class (e.g. Role::class).
	 * @param string|null $pivotTable Pivot table name (defaults to alphabetical join of both tables) converted to plural.
	 * @param string|null $foreignPivot FK on pivot for this model (defaults to snake-case this model + "_id").
	 * @param string|null $relatedPivot FK on pivot for the related model (defaults to snake-case related model + "_id").
	 * @param string|null $parentKey PK on this model (defaults to this model's key).
	 * @param string|null $relatedKey PK on related model (defaults to related model's key).
	 * @return Relations\BelongsToMany
	 */
	public function belongsToMany(
		string $related,
		?string $pivotTable = null,
		?string $foreignPivot = null,
		?string $relatedPivot = null,
		?string $parentKey = null,
		?string $relatedKey = null
	): Relations\BelongsToMany
	{
		$foreignClass = Strings::snakeCase(static::getIdClassName());
		$relatedClass = Strings::snakeCase($related::getIdClassName());

		// 1) Determine default pivot table name if none given:
		if ($pivotTable === null)
		{
			$tables = [
				$foreignClass,
				$relatedClass
			];

			sort($tables, SORT_STRING); // sort for consistent naming
			$pivotTable = implode('_', $tables) . 's'; // pluralize
		}

		// 2) Determine FKs on pivot if none given
		$parentKey = $parentKey ?? $this->getIdKeyName();
		$foreignPivot = $foreignPivot ?? ($foreignClass . '_id');

		$relatedKey = $relatedKey ?? $related::idKeyName();
		$relatedPivot = $relatedPivot ?? ($relatedClass . '_id');

		return new Relations\BelongsToMany(
			$related,
			$pivotTable,
			$foreignPivot,
			$relatedPivot,
			$parentKey,
			$relatedKey,
			$this
		);
	}

	/**
	 * Call a method on a given callable.
	 *
	 * @param array $callable Callable to execute.
	 * @param array|null $arguments Arguments for the callable.
	 * @return mixed
	 */
	public function callMethod(array $callable, ?array $arguments): mixed
	{
		if (!\is_callable($callable))
		{
			return false;
		}

		return \call_user_func_array($callable, $arguments);
	}

	/**
	 * Wrap a method call and optionally return a model instance.
	 *
	 * @param array $callable Callable to execute.
	 * @param array $arguments Arguments for the callable.
	 * @return mixed
	 */
	protected function wrapMethodCall(array $callable, array $arguments): mixed
	{
		$result = $this->callMethod($callable, $arguments);
		if (is_bool($result))
		{
			return $result;
		}

		if (is_int($result) || is_float($result))
		{
			return $result;
		}

		if (is_string($result))
		{
			return $result;
		}

		if (!isset($result->rows) && !is_array($result))
		{
			return ($result) ? new static($result) : null;
		}

		if (isset($result->rows))
		{
			$result->rows = $this->convertRows($result->rows);
			return $result;
		}
		return $this->convertRows($result);
	}

	/**
	 * Magic method to handle calls to storage methods.
	 *
	 * @param string $method Method name.
	 * @param array $arguments Method arguments.
	 * @return mixed
	 */
	public function __call(string $method, array $arguments): mixed
	{
		if ($this->isRelation($method))
		{
			return $this->callRelation($method);
		}

		$callable = [$this->storage, $method];
		return $this->wrapMethodCall($callable, $arguments);
	}

	/**
	 * Magic static method to handle calls to storage methods.
	 *
	 * @param string $method Method name.
	 * @param array $arguments Method arguments.
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $arguments): mixed
	{
		$model = new static();
		$callable = [$model->storage, $method];
		$result = $model->callMethod($callable, $arguments);
		if (is_bool($result))
		{
			return $result;
		}

		if (is_int($result) || is_float($result))
		{
			return $result;
		}

		if (is_string($result))
		{
			return $result;
		}

		return $model->storage->normalize($result);
	}

	/**
	 * Get model data as a formatted object.
	 *
	 * @return object
	 */
	public function getData(): object
	{
		return static::format($this->data->getData());
	}

	/**
	 * Check if a field exists in the model.
	 *
	 * @param string $key Field name.
	 * @return bool
	 */
	public function has(string $key): bool
	{
		if (empty($key))
		{
			return false;
		}

		return in_array($key, static::$fields, true);
	}

	/**
	 * Get the list of model fields.
	 *
	 * @return array
	 */
	public static function fields(): array
	{
		return static::$fields;
	}

	/**
	 * Get the list of model fields.
	 *
	 * @return array
	 */
	public function getFields(): array
	{
		return static::$fields;
	}

	/**
	 * Get the compiled joins.
	 *
	 * @return array
	 */
	public function getJoins(): array
	{
		return $this->compiledJoins;
	}

	/**
	 * Format the data (override as needed).
	 *
	 * @param object|null $data Data object.
	 * @return object|null
	 */
	protected static function format(?object $data): ?object
	{
		return $data;
	}

	/**
	 * Augment data before mapping (override as needed).
	 *
	 * @param mixed $data Data.
	 * @return mixed
	 */
	protected static function augment(mixed $data = null): mixed
	{
		return $data;
	}

	/**
	 * Get mapped data.
	 *
	 * @return object
	 */
	public function getMappedData(): object
	{
		return $this->augment($this->data->map());
	}

	/**
	 * Set up storage connection.
	 *
	 * @return StorageProxy
	 */
	protected function setupStorage(): StorageProxy
	{
		$className = static::$storageType;
		$storageInstance = new $className($this);

		/**
		 * The storage is wrapped in a proxy to dispatch events
		 * for all actions the storage layer is calling.
		 */
		$eventProxy = new StorageProxy($this, $storageInstance);
		return $this->storage = $eventProxy;
	}

	/**
	 * Get storage wrapper.
	 *
	 * @return StorageWrapper
	 */
	public function storage(): StorageWrapper
	{
		return $this->storageWrapper
			?? ($this->storageWrapper = new StorageWrapper($this->storage));
	}

	/**
	 * This will get a new table instance for the model.
	 *
	 * @param mixed $tableName
	 * @param mixed $alias
	 * @return QueryHandler
	 */
	public function getTable(?string $tableName = null, ?string $alias = null): QueryHandler
	{
		return $this->storage->table($tableName ?? static::$tableName, $alias ?? static::$alias);
	}

	/**
	 * Add model data to storage.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		return $this->storage->add();
	}

	/**
	 * Create a new model record.
	 *
	 * @param object|null $data Data object.
	 * @return bool
	 */
	public static function create(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->add();
	}

	/**
	 * Merge model data into storage.
	 *
	 * @return bool
	 */
	public function merge(): bool
	{
		return $this->storage->merge();
	}

	/**
	 * Update model status.
	 *
	 * @return bool
	 */
	public function updateStatus(): bool
	{
		return $this->storage->updateStatus();
	}

	/**
	 * Update model data in storage.
	 *
	 * @return bool
	 */
	public function update(): bool
	{
		return $this->storage->update();
	}

	/**
	 * Edit model record.
	 *
	 * @param object|null $data Data object.
	 * @return bool
	 */
	public static function edit(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->update();
	}

	/**
	 * Setup model storage (insert or update).
	 *
	 * @return bool
	 */
	public function setup(): bool
	{
		return $this->storage->setup();
	}

	/**
	 * Put model record into storage.
	 *
	 * @param object|null $data Data object.
	 * @return bool
	 */
	public static function put(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->setup();
	}

	/**
	 * Delete model record from storage.
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		return $this->storage->delete();
	}

	/**
	 * Remove model record from storage.
	 *
	 * @param object|null $data Data object.
	 * @return bool
	 */
	public static function remove(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->delete();
	}

	/**
	 * Search the table.
	 *
	 * @param mixed $search Search criteria.
	 * @return array
	 */
	public static function search(mixed $search): array
	{
		$instance = new static();
		$rows = $instance->storage->search($search);
		return $instance->convertRows($rows);
	}

	/**
	 * Get a record by identifier.
	 *
	 * @param int|string $id Identifier.
	 * @return static|null
	 */
	public static function get(mixed $id): ?static
	{
		$instance = new static();
		$row = $instance->storage->get($id);
		return ($row) ? new static($row) : null;
	}

	/**
	 * Count records.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $modifiers Modifiers.
	 * @return object|false
	 */
	public static function count(mixed $filter = null, ?array $modifiers = null): object|false
	{
		$instance = new static();
		return $instance->storage->count($filter, $modifiers);
	}

	/**
	 * Retrieve all records.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object|false
	 */
	public static function all(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object|false
	{
		return static::getRows($filter, $offset, $limit, $modifiers);
	}

	/**
	 * Get rows from storage.
	 *
	 * @param array $filter Filter criteria.
	 * @return object|null
	 */
	public static function getBy(array $filter): ?object
	{
		$instance = new static();
		$row = $instance->storage->getBy($filter);
		if ($row)
		{
			$row = $instance->convertRows([$row]);
			return $row[0] ?? null;
		}

		return $row;
	}

	/**
	 * Get rows from storage.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $params Query parameters.
	 * @param array|null $modifiers Query modifiers.
	 * @return AdapterProxy
	 */
	public static function where(mixed $filter = null, ?array $params = null, ?array $modifiers = null): AdapterProxy
	{
		$instance = new static();
		/**
		 * @SuppressWarnings PHP0408,PHP0423
		 */
		$adapterProxy = $instance->storage->where($filter, $params, $modifiers);
		$adapterProxy->addParams($params);
		return $adapterProxy;
	}

	/**
	 * Gets all rows by the where filter.
	 *
	 * @param array|object|null $filter
	 * @return array
	 */
	public static function fetchWhere(mixed $filter): ?array
	{
		return static::getRows($filter)->rows ?? null;
	}

	/**
	 * Get rows from storage.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object|false
	 */
	public static function getRows(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object|false
	{
		$instance = new static();
		$result = $instance->storage->getRows($filter, $offset, $limit, $modifiers);
		if ($result !== false && !empty($result->rows))
		{
			$result->rows = $instance->convertRows($result->rows);
		}

		return $result;
	}

	/**
	 * Convert raw rows to mapped data.
	 *
	 * @param array $rows Raw rows.
	 * @return array
	 */
	public function convertRows(array $rows): array
	{
		$rows = array_map([$this, 'augment'], $rows);
		$rows = $this->data->convertRows($rows);
		return array_map([$this, 'format'], $rows);
	}

	/**
	 * List rows as a Collection.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $count Count.
	 * @param array|null $modifiers Modifiers.
	 * @return Collection
	 */
	public function list(mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null): Collection
	{
		$result = $this->getRows($filter, $offset, $count, $modifiers);
		$rows = $result->rows ?? [];
		return new Collection($rows);
	}

	/**
	 * Render the last storage error for debugging.
	 *
	 * @return void
	 */
	public function debug(): void
	{
		Debug::render((string)$this->storage->getLastError());
	}

	/**
	 * Specify data for JSON serialization.
	 *
	 * @return mixed
	 */
	public function jsonSerialize(): mixed
	{
		return $this->getData();
	}

	/**
	 * Convert model data to a string.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return json_encode($this->getData());
	}
}
