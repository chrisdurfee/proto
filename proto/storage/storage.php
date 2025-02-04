<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Base;
use Proto\Models\ModelInterface;
use Proto\Database\Database;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Database\Adapters\SQL\Mysql\MysqliBindTrait;
use Proto\Utils\Strings;

/**
 * Storage
 *
 * The storage class can create objects to interact with the
 * database.
 *
 * These storage objects can do normal CRUD operations.
 *
 * @package Proto\Storage
 */
class Storage extends Base
{
	use MysqliBindTrait;

	/**
	 * @var ModelInterface $model
	 */
	protected ModelInterface $model;

	/**
	 * @var string $tableName
	 */
	protected string $tableName;

	/**
	 * @var string $alias
	 */
	protected string $alias;

	/**
	 * @var object $db Database class.
	 */
	protected object $db;

	/**
	 * @var string $connection
	 */
	protected $connection = 'proto';

	/**
	 * @var object|null $lastError
	 */
	protected ?object $lastError = null;

	/**
	 * @var string $compiledSelect
	 */
	protected static string $compiledSelect;

	/**
	 *
	 * @param ModelInterface $model
	 * @param string $tableName
	 * @param string $database
	 * @return void
	 */
    public function __construct(
		ModelInterface $model,
		string $tableName,
		protected string $database = Database::class
	)
    {
		$this->model = $model;
        $this->tableName = $tableName;
		$this->alias = $this->getAlias();
		$this->setConnection();
    }

	/**
	 * This will set the database class.
	 *
	 * @param string $database
	 * @return void
	 */
	public function setDatabase(string $database): void
	{
		$this->database = $database;
	}

	/**
	 * This will get a connection to the database.
	 *
	 * @return object|bool
	 */
    public function setConnection(): object|false
    {
		$connection = $this->getConnection();
        $db = new $this->database();
        return ($this->db = $db->connect($connection));
    }

	/**
	 * This will get the database connection.
	 *
	 * @return string|bool
	 */
	protected function getConnection(): string|false
	{
		$connection = $this->connection;
		if (!$connection)
		{
			$this->createNewError('No database connection is set');
		}
		return $connection ?? false;
	}

	/**
	 * This will set the last error.
	 *
	 * @param object $error
	 * @return void
	 */
	protected function setLastError(object $error): void
	{
		$this->lastError = $error;
	}

	/**
	 * This will get the last error.
	 *
	 * @return object|null
	 */
	public function getLastError(): ?object
	{
		return $this->lastError ?? $this->db->getLastError();
	}

	/**
	 * This will create a new error.
	 *
	 * @param string $message
	 * @param int|null $code
	 * @return void
	 */
	protected function createNewError(string $message, ?int $code = null): void
	{
		$this->setLastError(new \Exception($message, $code));
	}

	/**
	 * This will get the model data.
	 *
	 * @return object|null
	 */
	protected function getData(): ?object
	{
		$model = $this->model;
		if (!$model)
		{
			return null;
		}

		return $model->getMappedData();
	}

	/**
	 * This will fetch from the connection.
	 *
	 * @param string|object $sql
	 * @param array $params
	 * @return array|bool
	 */
	public function fetch(string|object $sql, array $params = []): array|false
	{
		return $this->db->fetch((string)$sql, $params);
	}

	/**
	 * This will fetch the rows from the connection.
	 *
	 * @param string|object $sql
	 * @param array $params
	 * @return array
	 */
	public function rows(string|object $sql, array $params = []): array
	{
		return $this->fetch((string)$sql, $params) ?? [];
	}

	/**
	 * This will fetch the first row from the connection.
	 *
	 * @param string|object $sql
	 * @param array $params
	 * @return mixed
	 */
	public function first(string|object $sql, array $params = []): mixed
	{
		$result = $this->db->fetch((string)$sql, $params);
		return $result[0] ?? null;
	}

	/**
	 * This will get the table alias.
	 *
	 * @return string
	 */
	protected function getAlias(): string
	{
		return $this->model->getAlias() ?? $this->tableName;
	}

	/**
	 * This will setup a query builder.
	 *
	 * @param string|null $alias
	 * @return object
	 */
	public function table(?string $alias = null): object
	{
		$alias = $alias ?? $this->model->getAlias();
		return $this->db->table($this->tableName, $alias);
	}

	/**
	 * This will execute a query from the connection.
	 *
	 * @param string $sql
	 * @param array $params
	 * @return bool
	 */
	public function execute(string|object $sql, array $params = []): bool
	{
		return $this->db->execute((string)$sql, $params);
	}

	/**
	 * This will make a transaction from the connection.
	 *
	 * @param string|object $sql
	 * @param array $params
	 * @return bool
	 */
	public function transaction(string|object $sql, array $params = []): bool
	{
		return $this->db->transaction((string)$sql, $params);
	}

	/**
	 * This will get the insert data.
	 *
	 * @return object
	 */
	protected function getInsertData()
	{
		$data = $this->getData();
		if ($this->model->has('createdAt') && !isset($data->created_at))
		{
            $data->created_at = date('Y-m-d H:i:s');
        }
		return $data;
	}

	/**
	 * This will add the model data to the table.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		$data = $this->getInsertData();
		return $this->insert($data);
	}

	/**
	 * This will insert data into the table.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function insert(object $data): bool
	{
		$result = $this->db->insert($this->tableName, $data);
		if (!isset($data->id))
		{
			$this->setModelId($result);
		}
		return $result;
	}

	/**
	 * This will merge the model data to the table by
	 * adding or updating the row. This needs to have a
	 * unique key set.
	 *
	 * @return bool
	 */
	public function merge(): bool
	{
		$data = $this->getInsertData();

		/**
		 * We might be updating the row so we need to
		 * get set the updated at value.
		 */
		if ($this->model->has('updatedAt'))
		{
            $data->updated_at = date('Y-m-d H:i:s');
        }

		return $this->replace($data);
	}

	/**
	 * This will insert data into the table.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function replace(object $data): bool
	{
		$result = $this->db->replace($this->tableName, $data);
		if (!isset($data->id))
		{
			$this->setModelId($result);
		}
		return $result;
	}

	/**
	 * This will set the model id.
	 *
	 * @param bool $result
	 * @return void
	 */
	protected function setModelId(bool $result = false): void
	{
		if ($result === true)
		{
			$this->model->setId($this->db->getLastId());
		}
	}

	/**
	 * This will get the model id key name.
	 *
	 * @return string
	 */
	protected function getModelIdKeyName(): string
	{
		return $this->model->getIdKeyName();
	}

	/**
	 * This will get the model id value.
	 *
	 * @return mixed
	 */
	protected function getModelIdValue(object $data, string $idKeyName): mixed
	{
		return (isset($data->{$idKeyName}))? $data->{$idKeyName} : null;
	}

	/**
	 * This will update the item status.
	 *
	 * @return bool
	 */
	public function updateStatus()
    {
        $data = $this->getUpdateData();

        $dateTime = date('Y-m-d H:i:s');
        return $this->db->update($this->tableName, [
            'id' => $data->id,
            'status' => $data->status,
            'updated_at' => $dateTime
        ]);
    }

	/**
	 * This will update the model data to the table.
	 *
	 * @return bool
	 */
	public function update(): bool
	{
		$data = $this->getUpdateData();
		$key = $this->getModelIdKeyName();
		return $this->db->update($this->tableName, $data, $key);
	}

	/**
	 * This will get the udpate data.
	 *
	 * @return object
	 */
	protected function getUpdateData(): object
	{
		$data = $this->getData();
		if ($this->model->has('updatedAt'))
		{
            $data->updated_at = date('Y-m-d H:i:s');
        }
		return $data;
	}

	/**
	 * This will add or update the model to the table.
	 *
	 * @return bool
	 */
	public function setup(): bool
	{
		$data = $this->getData();
		return ($this->exists($data)? $this->update() : $this->add());
	}

	/**
	 * This will setup the exist result.
	 *
	 * @param array $rows
	 * @return bool
	 */
	protected function checkExistCount($rows): bool
	{
		$count = count($rows);
		if ($count < 1)
		{
			return false;
		}

		$row = $rows[0];
		$idKeyName = $this->model->getIdKeyName();
		$this->model->{$idKeyName} = $row->{$idKeyName} ?? null;
		return true;
	}

	/**
	 * This will check if the table aready has the model data.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists($data): bool
	{
        $idName = $this->model->getIdKeyName();
		$id = $data->{$idName} ?? null;
		if (!isset($id))
		{
			return false;
		}

		$rows = $this->select("{$idName}")
			->where("{$this->alias}.{$idName} = ?")
			->limit(1)
            ->fetch([$id]);

		return $this->checkExistCount($rows);
	}

	/**
	 * This will delete the model data from the table.
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		$data = $this->getData();
		$key = $this->getModelIdKeyName();
		$id = $this->getModelIdValue($data, $key);
		if ($id === null)
		{
			return false;
		}

		if ($this->model->has('deletedAt'))
		{
            return $this->db->update($this->tableName, [
				'deleted_at' => date('Y-m-d H:i:s'),
				"{$key}" => $id
			]);
		}

		return $this->db->delete($this->tableName, $id, $key);
	}

	/**
	 * This will decamelise a string.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function decamelize(string $str): string
    {
        return Strings::snakeCase($str);
    }

	/**
	 * This will normalize the data results form snake
	 * case to camel case.
	 *
	 * @param object|array $data
	 * @return mixed
	 */
	public function normalize(mixed $data): mixed
	{
		if (!$data)
		{
			return $data;
		}

		if (is_array($data))
		{
			$rows = [];
			foreach ($data as $row)
			{
				array_push($rows, Strings::mapToCamelCase($row));
			}
			return $rows;
		}
		else if (is_object($data))
		{
			return Strings::mapToCamelCase($data);
		}

		return $data;
	}

	/**
	 * This will get the model fields.
	 *
	 * @param array $joins
	 * @return array
	 */
	protected function getModelFields(array &$joins): array
	{
		$cols = [];

		$isSnakeCase = $this->model->isSnakeCase();
		$fields = $this->model->getFields();
		foreach ($fields as $field)
		{
			$field = static::formatField($field, $isSnakeCase);
			array_push($cols, $field);
		}

		if (count($joins) > 0)
		{
			$this->getJoinCols($joins, $cols);
		}

		return $cols;
	}

	/**
	 * This will format the fields.
	 *
	 * @param array|null $fields
	 * @return array|null
	 */
	protected function formatFields(?array $fields): ?array
	{
		if (count($fields) < 1)
		{
			return $fields;
		}

		$cols = [];
		$isSnakeCase = $this->model->isSnakeCase();
		foreach ($fields as $field)
		{
			$field = static::formatField($field, $isSnakeCase);
			array_push($cols, $field);
		}

		return $cols;
	}

	/**
	 * This will format a field.
	 *
	 * @param mixed $field
	 * @param bool $isSnakeCase
	 * @return mixed
	 */
	protected static function formatField(mixed $field, bool $isSnakeCase): mixed
	{
		if (is_array($field) === false)
		{
			return static::prepareFieldName($field, $isSnakeCase);
		}

		// raw sql
		if (count($field) < 2)
		{
			return $field;
		}

		//alias
		if (is_array($field[0]) === false)
		{
			return [static::prepareFieldName($field[0], $isSnakeCase), static::prepareFieldName($field[1], $isSnakeCase)];
		}

		//raw sql with alias
		return [$field[0], static::prepareFieldName($field[1], $isSnakeCase)];
	}

	/**
	 * This will prepare the field name.
	 *
	 * @param string $field
	 * @param bool $isSnakeCase
	 * @return string
	 */
	protected static function prepareFieldName(string $field, bool $isSnakeCase): string
	{
		return ($isSnakeCase)? static::decamelize($field) : $field;
	}

	/**
	 * This will get the join columns.
	 *
	 * @param array $joins
	 * @param array $cols
	 * @return void
	 */
	protected function getJoinCols(array &$joins, array &$cols): void
	{
		foreach ($joins as $join)
		{
			$table = $join->getTableName();
			if (!$table)
			{
				continue;
			}

			$multiple = $join->isMultiple();
			if ($multiple === false)
			{
				continue;
			}

			// add child join
			$col = [$this->setupSubQuery($join)];
			array_push($cols, $col);
		}
	}

	/**
	 * This will create a group concat string.
	 *
	 * @param string $as
	 * @param array $fields
	 * @return string
	 */
	protected function getGroupConcatSql(string $as, array $fields): string
	{
		/**
		 * We want to create the key names for the fields.
		 */
		$keys = array_map(function($field)
		{
			return "'{$field}-:-', {$field}";
		}, $fields);

		$concat = implode(", '-::-', ", $keys);
		return "GROUP_CONCAT({$concat} SEPARATOR '-:::-') AS {$as}";
	}

	/**
	 * This will create a new query builder.
	 *
	 * @param string $tableName
	 * @param string|null $alias
	 * @return QueryHandler
	 */
	protected function builder(string $tableName, ?string $alias = null): QueryHandler
	{
		return new QueryHandler($tableName, $alias, $this->db);
	}

	/**
	 * This will add the child join.
	 *
	 * @param array $joins
	 * @param object $join
	 * @param array $fields
	 * @return void
	 */
	protected function addChildJoin(array &$joins, object $join, array &$fields): void
	{
		$childJoin = $join->getMultipleJoin();
		if ($childJoin)
		{
			$childFields = $this->formatFields($childJoin->getFields());
			$fields = array_merge($fields, $childFields);

			array_push($joins, [
				'table' => $childJoin->getTableName(),
				'type' => $childJoin->getType(),
				'alias' => $childJoin->getAlias(),
				'on' => $childJoin->getOn(),
				'using' => $childJoin->getUsing(),
			]);

			$this->addChildJoin($joins, $childJoin, $fields);
		}
	}

	/**
	 * This will create a sub query string.
	 *
	 * @param object $join
	 * @return string
	 */
	protected function setupSubQuery(object $join): string
	{
		$tableName = $join->getTableName();
		$alias = $join->getAlias();
		$builder = $this->builder($tableName, $alias);

		$fields = $this->formatFields($join->getFields());

		$joins = [];
		$this->addChildJoin($joins, $join, $fields);

		$as = $join->getAs();
		$groupConcat = $this->getGroupConcatSql($as, $fields);
		$sql = $builder->select([$groupConcat])->joins($joins);

		return '(' . $sql->where(...$join->getOn()) . ') AS ' . $as;
	}

	/**
	 * This will get the col names.
	 *
	 * @param array $joins
	 * @return array
	 */
	protected function getColNames(array $joins): array
	{
		return $this->getModelFields($joins);
	}

	/**
	 * This will map the model joins to array.
	 *
	 * @param array|null $joins
	 * @param bool $allowFields
	 * @return array|null
	 */
	protected function getMappedJoins(?array &$joins = null, bool $allowFields = true): ?array
	{
		if (count($joins) < 1)
		{
			return $joins;
		}

		$mapped = [];
		foreach ($joins as $join)
		{
			array_push($mapped, [
				'table' => $join->getTableName(),
				'alias' => $join->getAlias(),
				'type' => $join->getType(),
				'on' => $join->getOn(),
				'fields' => ($allowFields)? $this->formatFields($join->getFields()) : null
			]);
		}
		return $mapped;
	}

    /**
     * This will get custom fields with join fields.
     *
     * @param mixed ...$fields
     * @return array
     */
    protected function getCustomFields(...$fields): array
    {
        if (count($fields))
        {
            $joins = $this->model->getJoins();
            array_push($fields, $this->getColNames($joins));
        }

        return $fields;
    }

	/**
	 * This will create a query builder to select.
	 *
	 * @param mixed ...$fields
	 * @return object
	 */
	public function select(...$fields)
	{
		$joins = $this->model->getJoins();
		$colNames = [];
		$allowFields = true;

		if (count($fields))
		{
			$colNames = $fields;
			$allowFields = false;
		}
		else
		{
			$colNames = $this->getColNames($joins);
		}

		$joins = $this->getMappedJoins($joins, $allowFields);
		return $this->table()->select(...$colNames)->joins($joins);
	}

	/**
	 * This will create a select string.
	 *
	 * @param array|null $modifiers
	 * @return string
	 */
	protected function getSelectSql(?array $modifiers = null): string
	{
		return $this->select() . ' ';
	}

	/**
	 * This will search for a row by the model id.
	 *
	 * @param string $search
	 * @return array
	 */
	public function search(string $search = '')
	{
		return $this->select()
			->where("{$this->alias}.id = ?")
			->limit(0, 20)
			->fetch([$search]);
	}

	/**
	 * This will get a table row by id.
	 *
	 * @param int|string $id
	 * @return object|bool
	 */
	public function get($id)
	{
		return $this->select()
			->where("{$this->alias}.id = ?")
			->first([$id]);
	}

	/**
	 * This will get a table row by filter.
	 *
	 * @param array|object $filter
	 * @return object|bool
	 */
	public function getBy($filter)
    {
        $params = [];
        $where = static::setFilters($filter, $params);

        return $this->select()
            ->where(...$where)
			->first($params);
    }

	/**
	 * This will set the getRows order by.
	 *
	 * @param object $sql
	 * @param array|null $modifiers
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null)
	{

	}

	/**
	 * This will get rows by filter, limit, and modifiers.
	 *
	 * @param array|object|null $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return object
	 */
	public function getRows($filter = null, $offset = null, $count = null, ?array $modifiers = null)
	{
		$params = [];
		$where = static::getWhere($params, $filter, $modifiers);

		$sql = $this->select()
            ->where(...$where)
            ->limit($offset, $count);

		$this->setOrderBy($sql, $modifiers);

        $rows = $this->fetch($sql, $params);

		return (object)[
		    'rows' => $rows ?? []
        ];
	}

	/**
	 * This will set the filters.
	 *
	 * @param array|object|null $filter
	 * @param array $params
	 * @return array
	 */
	protected static function setFilters($filter = null, array &$params = []): array
	{
		return Filter::setup($filter, $params);
	}

	/**
	 * This will allow the where to be modified by modifiers.
	 *
	 * @param array $where
	 * @param array|null $modifiers
	 * @param array $params
	 * @param array $filter
	 * @return void
	 */
	protected static function setModifiers(
		array &$where = [],
		?array $modifiers = null,
		array &$params = [],
		$filter = null
	)
	{

	}

	/**
	 * This will setup the select where condtions.
	 *
	 * @param array $params
	 * @param array|object|null $filter
	 * @param array|null $modifiers
	 * @return array
	 */
	protected static function getWhere(array &$params, $filter, ?array $modifiers = null): array
	{
		$where = static::setFilters($filter, $params);
		static::setModifiers($where, $modifiers, $params, $filter);
		return $where;
	}

	/**
	 * This will select all rows.
	 *
	 * @param array|object|null $filter
	 * @param array $params
	 * @param array|null $modifiers
	 * @return object
	 */
	public function where($filter, array &$params, ?array $modifiers = null): object
	{
		$where = static::getWhere($params, $filter, $modifiers);

		return $this->select()
			->where(...$where);
	}

	/**
	 * This will select all rows.
	 *
	 * @param array|object|null $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return array|bool
	 */
	public function all($filter = null, $offset = null, $count = null, ?array $modifiers = null)
	{
		$params = [];
		$sql = $this->where($filter, $params, $modifiers)
			->limit($offset, $count);

		return $this->fetch($sql, $params);
	}

	/**
	 * This will call a callBack.
	 *
	 * @param callable $callBack
	 * @param object $sql
	 * @param array $params
	 * @return void
	 */
	protected function callBack(
		callable $callBack,
		object $sql,
		array &$params = []
	): void
    {
        if ($callBack)
        {
            call_user_func_array($callBack, [$sql, $params]);
        }
    }

	/**
	 * This will allow a callBack to be passed to setup
	 * a select query builder.
	 *
	 * @param callable $callBack
	 * @return mixed
	 */
	public function find(callable $callBack)
	{
		$params = [];
		$sql = $this->select();
		$this->callBack($callBack, $sql, $params);
		return $this->first((string)$sql, $params);
	}

	/**
	 * This will allow a callBack to be passed to setup
	 * a select query builder.
	 *
	 * @param callable $callBack
	 * @return array|bool
	 */
	public function findAll(callable $callBack)
	{
		$params = [];
		$sql = $this->select();
		$this->callBack($callBack, $sql, $params);
		return $this->fetch((string)$sql, $params);
	}

	/**
	 * This will get a row count.
	 *
	 * @param array|object|null $filter
	 * @param array|null $modifiers
	 * @return object
	 */
	public function count($filter = null, ?array $modifiers = null): object
    {
		$params = [];
		$where = self::getWhere($params, $filter, $modifiers);

        $sql = $this->select(
			[['COUNT(*)'], 'count']
		)->where(...$where);

        return $this->first($sql, $params);
	}
}
