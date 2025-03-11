<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Base;
use Proto\Models\Data\Data;
use Proto\Storage\Storage;
use Proto\Storage\StorageProxy;
use Proto\Tests\Debug;
use Proto\Support\Collection;
use Proto\Utils\Strings;
use Proto\Models\Joins\JoinBuilder;
use Proto\Models\Joins\ModelJoin;

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
	 * Join builder instance.
	 *
	 * @var JoinBuilder|null
	 */
	protected ?JoinBuilder $builder = null;

	/**
	 * Fields to exclude when exporting.
	 *
	 * @var array
	 */
	protected static array $fieldsBlacklist = [];

	/**
	 * Storage connection instance.
	 *
	 * @var object|null
	 */
	public ?object $storage = null;

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
	protected bool $isSnakeCase = true;

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
	public function getIdKeyName(): string
	{
		return $this->isSnakeCase === true ? Strings::snakeCase(static::$idKeyName) : static::$idKeyName;
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
		$builder = $this->builder = new JoinBuilder($joins, static::$tableName, $alias, $this->isSnakeCase);

		// Set the model class name for joins.
		$modelClassName = static::getIdClassName();
		$builder->setModelClassName($modelClassName);

		// Call the joins method.
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
	 * Set up a one-to-many join.
	 *
	 * @param JoinBuilder $builder
	 * @param string $type Join type.
	 * @return ModelJoin
	 */
	public static function oneToMany(JoinBuilder $builder, string $type = 'left'): ModelJoin
	{
		$result = static::oneToOne($builder, $type);
		$result->multiple();
		return $result;
	}

	/**
	 * Set up a one-to-one join.
	 *
	 * @param JoinBuilder $builder
	 * @param string $type Join type.
	 * @return ModelJoin
	 */
	public static function oneToOne(JoinBuilder $builder, string $type = 'left'): ModelJoin
	{
		$child = $builder->{$type}(static::table(), static::alias());
		$idName = static::getIdClassName();
		$child->on(['id', $idName . 'Id']);
		return $child;
	}

	/**
	 * Set up model joins.
	 *
	 * @return void
	 */
	protected function setupJoins(): void
	{
		$joins = $this->getModelJoins();
		if (count($joins) < 1)
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
		return $this->isSnakeCase;
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
			$this->isSnakeCase
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
	 * Magic setter for model properties.
	 *
	 * @param string $key Property name.
	 * @param mixed $value Property value.
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->set($key,$value);
	}

	/**
	 * Magic getter for model properties.
	 *
	 * @param string $key Property name.
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $this->data->get($key);
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
		if ($method === 'many')
		{
			return static::oneToMany(...$arguments);
		}

		if ($method === 'one')
		{
			return static::oneToOne(...$arguments);
		}

		$model = new static();
		$callable = [$model->storage, $method];
		$result = $model->callMethod($callable, $arguments);
		if (is_bool($result))
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

		return in_array($key, static::$fields);
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
	 * @return object
	 */
	protected function setupStorage(): object
	{
		$className = static::$storageType;
		$storageInstance = new $className($this);
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
		return $this->storageWrapper ?? ($this->storageWrapper = new StorageWrapper($this->storage));
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
	 * @return object|null
	 */
	public static function get(mixed $id): ?object
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
	 * @param int|null $count Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object|false
	 */
	public static function all(mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null): object|false
	{
		return static::getRows($filter, $offset, $count, $modifiers);
	}

	/**
	 * Get rows from storage.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $count Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object|false
	 */
	public static function getRows(mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null): object|false
	{
		$instance = new static();
		$result = $instance->storage->getRows($filter, $offset, $count, $modifiers);
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
		$rows = array_map([$this, 'augment'],$rows);
		$rows = $this->data->convertRows($rows);
		return array_map([$this, 'format'],$rows);
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
