<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Base;
use Proto\Storage\Storage;
use Proto\Storage\StorageProxy;
use Proto\Tests\Debug;
use Proto\Support\Collection;
use Proto\Utils\Strings;

/**
 * Model
 *
 * This will be the base model class. This will set and get the
 * model data.
 *
 * The model can access the storage property to interace with a
 * storage object to allow the data to persist.
 *
 * @package Proto\Models
 * @abstract
 */
abstract class Model extends Base implements \JsonSerializable, ModelInterface
{
	/**
	 * @var string|null $tableName
	 */
	protected static $tableName;

	/**
	 * @var string|null $alias
	 */
	protected static $alias;

	/**
	 * @var string $idKeyName
	 */
	protected static $idKeyName = 'id';

	/**
	 * @var array $fields
	 */
	protected static $fields = [];

	/**
	 * @var bool $passModel
	 */
	protected $passModel = false;

	/**
	 * @var array $joins
	 */
	protected static $joins = [];

	/**
	 * @var array $compiledJoins
	 */
	protected array $compiledJoins = [];

	/**
	 * The fields to exclude when exported.
	 *
	 * @var array $fieldsBlacklist
	 */
	protected static $fieldsBlacklist = [];

	/**
	 * The storage connection to the database.
	 *
	 * @var object $storage
	 */
	public object $storage;

	/**
	 * @var ?StorageWrapper $storageWrapper
	 */
	public ?StorageWrapper $storageWrapper = null;

	/**
	 * The model storage type
	 *
	 * @var string $storageType
	 */
	protected static $storageType = Storage::class;

	/**
	 * The model data mapper.
	 *
	 * @var DataMapper $data
	 */
	protected DataMapper $data;

	/**
	 * This will setup the model with data.
	 *
	 * @param object|null $data
	 * @return void
	 */
	public function __construct(?object $data = null)
    {
        parent::__construct();
		$this->init($data);
    }

	/**
	 * This will setup the model data.
	 *
	 * @param object|null $data
	 * @return void
	 */
	protected function init(?object $data)
	{
		$this->setupJoins();
		$this->setupDataMapper();
		$this->setupStorage();

		// this will augment the data before being passed to the data mapper
		$data = static::augment($data);
		$this->data->set($data);
	}

	/**
	 * This will set the model storage type.
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
	 * This will get the id key name.
	 *
	 * @return string
	 */
	public function getIdKeyName(): string
	{
		return $this->isSnakeCase === true? Strings::snakeCase(static::$idKeyName) : static::$idKeyName;
	}

	/**
	 * This will set the model id key value.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function setId(mixed $value): void
	{
		$key = $this->getIdKeyName();
		$this->set($key, $value);
	}

	/**
	 * This will get the model joins.
	 *
	 * @return array
	 */
	protected function getModelJoins(): array
	{
		if (method_exists(static::class, 'joins'))
		{
			$joins = [];
			$alias = static::$alias ?? null;

			$builder = new JoinBuilder($joins, static::$tableName, $alias, $this->isSnakeCase);

			$modelClassName = static::getIdClassName();
			$builder->setModelClassName($modelClassName);

			$value = static::class . '::joins';
			\call_user_func($value, $builder);
			return $joins;
		}

		return static::getMappedJoins();
	}

	/**
	 * This will get the class
	 *
	 * @return string
	 */
	public static function getIdClassName(): string
	{
		/**
		 * This will add a default on using the name of the
		 * class added to Id.
		 */
		$className = (new \ReflectionClass(static::class))
			->getShortName();

		return (Strings::lowercaseFirstChar($className));
	}

	/**
	 * This will setup a one to one join.
	 *
	 * @param object $builder
	 * @param string $type
	 * @return object
	 */
	public static function oneToMany($builder, string $type = 'left'): object
	{
		$result = static::oneToOne($builder, $type);
		$result->multiple();
		return $result;
	}

	/**
	 * This will setup a one to one join.
	 *
	 * @param object $builder
	 * @param string $type
	 * @return object
	 */
	public static function oneToOne($builder, string $type = 'left'): object
	{
		$child = $builder->{$type}(static::table(), static::alias());

		/**
		 * This will add a default on using the name of the
		 * class added to Id.
		 */
		$idName = static::getIdClassName();
		$child->on(['id', $idName . 'Id']);
		return $child;
	}

	/**
	 * This will convert the model joins.
	 *
	 * @return array|null
	 */
	protected static function getMappedJoins(): ?array
	{
		$joins = static::$joins;
		if (count($joins) < 1)
		{
			return $joins;
		}

		$alias = static::$alias ?? null;
		return JoinMapper::mapJoins(static::class, static::$tableName, $alias, $joins);
	}

	/**
	 * This will setup the model joins.
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
	 * @var bool $isSnakeCase Whether or not the data is snake_case.
	 */
	protected $isSnakeCase = true;

	/**
	 * This will get if the data is snake_case.
	 *
	 * @return bool
	 */
	public function isSnakeCase(): bool
	{
		return $this->isSnakeCase;
	}

	/**
	 * This will setup the data mapper.
	 *
	 * @return void
	 */
	protected function setupDataMapper(): void
	{
		$this->data = new DataMapper(static::$fields, $this->compiledJoins, static::$fieldsBlacklist);
		$this->data->setSnakeCase($this->isSnakeCase);
	}

	/**
	 * This will get the table name.
	 *
	 * @return string|null
	 */
	public static function table(): ?string
	{
		return static::$tableName;
	}

	/**
	 * This will get the table name.
	 *
	 * @return string|null
	 */
	public function getTableName(): ?string
	{
		return static::$tableName;
	}

	/**
	 * This will get the table alias.
	 *
	 * @return string|null
	 */
	public static function alias(): ?string
	{
		return static::$alias;
	}

	/**
	 * This will get the alias.
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string
	{
		return static::$alias;
	}

	/**
	 * This will set the model data.
	 * The params are either an object or $key, $value
	 *
	 * @return self
	 */
	public function set(): self
	{
		$args = func_get_args();
		$this->data->set(...$args);
		return $this;
	}

	/**
	 * This will set the model data.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->set($key, $value);
	}

	/**
	 * This will get the value of a model property.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $this->data->get($key);
	}

	/**
	 * This will call the model method.
	 *
	 * @param array $value
	 * @param array|null $arguments
	 * @return mixed
	 */
	public function callMethod(array $value, ?array $arguments): mixed
	{
		if (!\is_callable($value))
        {
            return false;
		}

        return \call_user_func_array($value, $arguments);
	}

	/**
	 * This will wrap the method call in a model if passModel
	 * is set to true.
	 *
	 * @param array $value
	 * @param array $arguments
	 * @return mixed
	 */
	protected function wrapMethodCall(array $value, array $arguments): mixed
	{
		$result = $this->callMethod($value, $arguments);
		if ($this->passModel === false)
		{
			return $result;
		}

		if (is_bool($result) === true)
		{
			return $result;
		}

		$rowsNotSet = !isset($result->rows);
		if ($rowsNotSet && is_array($result) === false)
		{
			return ($result)? new static($result) : null;
		}

		if ($rowsNotSet === false)
		{
			$result->rows = $this->convertRows($result->rows);
			return $result;
		}

		return $this->convertRows($result);
	}

	/**
	 * This will allow the storage to be called directly without
	 * having to declare all methods in the model.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $method, array $arguments): mixed
    {
		$value = [$this->storage, $method];
        return $this->wrapMethodCall($value, $arguments);
	}

	/**
	 * This will allow the storage to be called directly without
	 * having to declare all methods in the model.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $arguments): mixed
    {
		if ($method === 'many')
		{
			$result = static::oneToMany(...$arguments);
			return $result;
		}
		else if ($method === 'one')
		{
			return static::oneToOne(...$arguments);
		}

		/**
		 * This will call the method non-statically on the storage.
		 */
		$model = new static();
		$value = [$model->storage, $method];

		/**
		 * If the result is a boolean, then we will return it.
		 */
        $result = $model->callMethod($value, $arguments);
		if (is_bool($result) === true)
		{
			return $result;
		}

		/**
		 * This will convert the result to camel case.
		 */
		return $model->storage->normalize($result);
    }

	/**
	 * This will get the model data.
	 *
	 * @return object
	 */
	public function getData(): object
	{
		return static::format($this->data->getData());
	}

	/**
	 * This will check if a model has a field.
	 *
	 * @param string $key
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
	 * This will get the model fields.
	 *
	 * @return array
	 */
	public function getFields(): array
	{
		return static::$fields;
	}

	/**
	 * This will get the model joins.
	 *
	 * @return array
	 */
	public function getJoins(): array
	{
		return $this->compiledJoins;
	}

	/**
	 * This can be used to format the data.
	 *
	 * @param object|null $data
	 * @return object|null
	 */
	protected static function format(?object $data): ?object
	{
		return $data;
	}

	/**
	 * This will allow you to augment the data after
	 * its added to the data mapper.
	 *
	 * @param mixed $data
	 * @return object
	 */
	protected static function augment($data = null)
	{
		return $data;
	}

	/**
	 * This will get the mapped data.
	 *
	 * @return object
	 */
	public function getMappedData(): object
	{
		return $this->augment($this->data->map());
	}

	/**
	 * This will setup the model storage.
	 *
	 * @return object
	 */
	protected function setupStorage(): object
	{
		$className = static::$storageType;
		$storage = new $className($this, static::$tableName);

		$eventProxy = new StorageProxy($this, $storage);
        return ($this->storage = $eventProxy);
	}

	/**
	 * This will get a storage wrapper to access the storage
	 * layer and normalize the results.
	 *
	 * @return StorageWrapper
	 */
	public function storage(): StorageWrapper
	{
		return $this->storageWrapper ?? ($this->storageWrapper = new StorageWrapper($this->storage));
	}

	/**
	 * This will insert the model data into the table.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		return $this->storage->add();
	}

	/**
	 * This will insert the model data into the table.
	 *
	 * @param object|null $data
	 * @return bool
	 */
	public static function create(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->add();
	}

	/**
	 * This will insert or update the model data into the table.
	 *
	 * @return bool
	 */
	public function merge(): bool
	{
		return $this->storage->merge();
	}

	/**
	 * This will update the model status.
	 *
	 * @return bool
	 */
	public function updateStatus()
	{
		return $this->storage->updateStatus();
	}

	/**
	 * This will update the model data into the table.
	 *
	 * @return bool
	 */
	public function update(): bool
	{
		return $this->storage->update();
	}

	/**
	 * This will update the model data into the table.
	 *
	 * @param object|null $data
	 * @return bool
	 */
	public static function edit(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->update();
	}

	/**
	 * This will insert or udpate the model data into the table.
	 *
	 * @return bool
	 */
	public function setup(): bool
	{
		return $this->storage->setup();
	}

	/**
	 * This will add or update the model data into the table.
	 *
	 * @param object|null $data
	 * @return bool
	 */
	public static function put(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->setup();
	}

	/**
	 * This will delete the model data from the table.
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		return $this->storage->delete();
	}

	/**
	 * This will delete the model data from the table.
	 *
	 * @param object|null $data
	 * @return bool
	 */
	public static function remove(?object $data = null): bool
	{
		$model = new static($data);
		return $model->storage->delete();
	}

	/**
	 * This will search the table.
	 *
	 * @param mixed $search
	 * @return array
	 */
	public static function search($search): array
	{
		$obj = new static();
		$rows = $obj->storage->search($search);
		return $obj->convertRows($rows);
	}

	/**
	 * This will get a row from the table by id.
	 *
	 * @param int|string $id
	 * @return object|bool
	 */
	public static function get($id)
	{
		$obj = new static();
		$row = $obj->storage->get($id);
		return ($row)? new static($row) : false;
	}

	/**
	 * This will get rows from a table.
	 *
	 * @param array|object $filter
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function count($filter = null, ?array $modifiers = null)
	{
		$obj = new static();
		return $obj->storage->count($filter, $modifiers);
	}

	/**
	 * This will get rows from a table.
	 *
	 * @param array|object $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function all($filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null)
	{
		return static::getRows($filter, $offset, $count, $modifiers);
	}

	/**
	 * This will get rows from a table.
	 *
	 * @param array|object $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function getRows(
		$filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	)
	{
		$obj = new static();
		$result = $obj->storage->getRows($filter, $offset, $count, $modifiers);
		if ($result !== false && count($result->rows) >= 1)
		{
			$result->rows = $obj->convertRows($result->rows);
		}

		return $result;
	}

	/**
	 * This will convert raw table rows to data mapped rows.
	 *
	 * @param array $rows
	 * @return array
	 */
	public function convertRows(array $rows): array
	{
		$rows = array_map([$this, 'augment'], $rows);
		$rows = $this->data->convertRows($rows);
		return array_map([$this, 'format'], $rows);
	}

	/**
	 * This will get a list of rows.
	 *
	 * @param array|object $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return Collection
	 */
	public function list(
		$filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): Collection
	{
		$rows = $this->getRows($filter, $offset, $count, $modifiers)->rows ?? [];
		return new Collection($rows);
	}

	/**
	 * This will display the last storage error.
	 *
	 * @return void
	 */
	public function debug(): void
	{
		Debug::render((string)$this->storage->getLastError());
	}

	/**
	 * This will return the model data when json encoded.
	 *
	 * @return mixed
	 */
	public function jsonSerialize(): mixed
    {
        return $this->getData();
    }

	/**
	 * This will convert the model data to a string.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return json_encode($this->getData());
	}
}