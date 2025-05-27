<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Models\ModelInterface;
use Proto\Models\Joins\ModelJoin;
use Proto\Database\Database;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Utils\Strings;
use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Database\Adapters\Adapter;
use Proto\Storage\Helpers\FieldHelper;
use Proto\Storage\Helpers\SubQueryHelper;

/**
 * Class Storage
 *
 * Provides CRUD operations using a fluent query builder.
 * SQL generation is delegated to the query builder,
 * while filter processing and raw SQL snippet generation are
 * handled by dedicated helper classes.
 *
 * @package Proto\Storage
 */
class Storage implements StorageInterface
{
	/**
	 * Table name.
	 * @var string
	 */
	protected string $tableName;

	/**
	 * Table alias.
	 * @var string
	 */
	protected string $alias;

	/**
	 * Database adapter instance.
	 * @var Adapter
	 */
	protected Adapter $db;

	/**
	 * Connection key.
	 * @var string
	 */
	protected string $connection = 'default';

	/**
	 * Last error encountered.
	 * @var \Throwable|null // Store Throwable for better error info
	 */
	protected ?\Throwable $lastError = null;

	/**
	 * Compiled select SQL.
	 * @var string
	 */
	protected static string $compiledSelect;

	/**
	 * Parameter casts for the model.
	 * @var array
	 */
	protected static array $paramCasts = [];

	/**
	 * Storage constructor.
	 *
	 * @param ModelInterface $model The model instance.
	 * @param string $database The database adapter class.
	 */
	public function __construct(
		protected ModelInterface $model,
		protected string $database = Database::class
	)
	{
		$this->tableName = $model->getTableName();
		$this->alias = $model->getAlias();
		$this->setConnection();
	}

	/**
	 * Set the database adapter class.
	 *
	 * @param string $database
	 * @return void
	 */
	public function setDatabase(string $database): void
	{
		$this->database = $database;
	}

	/**
	 * Establish a database connection.
	 *
	 * @return Adapter|false
	 */
	public function setConnection(): object|false
	{
		$conn = $this->getConnection();
		$db = new $this->database();
		return $this->db = $db->connect($conn);
	}

	/**
	 * Retrieve the connection key.
	 *
	 * @return string|false
	 */
	protected function getConnection(): string|false
	{
		$conn = $this->connection;
		if (!$conn)
		{
			$this->createNewError('No database connection is set');
		}
		return $conn ?: false;
	}

	/**
	 * Set the last error encountered.
	 *
	 * @param \Throwable $error The exception/error object.
	 * @return void
	 */
	protected function setLastError(\Throwable $error): void
	{
		$this->lastError = $error;
	}

	/**
	 * Retrieve the last error encountered by this storage or the underlying adapter.
	 *
	 * @return \Throwable|null
	 */
	public function getLastError(): ?\Throwable
	{
		return $this->lastError ?? new \Exception($this->db->getLastError());
	}

	/**
	 * Create and set a new error.
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
	 * Retrieve model-mapped data.
	 *
	 * @return object|null
	 */
	protected function getData(): ?object
	{
		return $this->model->getMappedData();
	}

	/**
	 * Fetch rows from the database.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return array|false
	 */
	public function fetch(string|object $sql, array $params = []): array|false
	{
		return $this->db->fetch((string)$sql, $params);
	}

	/**
	 * Execute a SQL statement.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return bool
	 */
	public function execute(string|object $sql, array $params = []): bool
	{
		return $this->db->execute((string)$sql, $params);
	}

	/**
	 * Execute a transaction.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return bool
	 */
	public function transaction(string|object $sql, array $params = []): bool
	{
		return $this->db->transaction((string)$sql, $params);
	}

	/**
	 * Create a query builder for the model table.
	 *
	 * @param string|null $alias Optional table alias.
	 * @return QueryHandler
	 */
	public function table(?string $alias = null): QueryHandler
	{
		$alias = $alias ?? $this->alias;
		return $this->db->table($this->tableName, $alias);
	}

	/**
	 * Prepare data for insertion.
	 *
	 * @return object
	 */
	protected function getInsertData(): object
	{
		$data = $this->getData();
		if ($this->model->has('createdAt') && !isset($data->created_at))
		{
			$data->created_at = date('Y-m-d H:i:s');
		}
		return $data;
	}

	/**
	 * Add a new record.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		$data = $this->getInsertData();
		return $this->insert($data);
	}

	/**
	 * Insert a record.
	 *
	 * @param object $data Data to insert.
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
	 * Merge (insert or update) a record.
	 *
	 * @return bool
	 */
	public function merge(): bool
	{
		$data = $this->getInsertData();
		if ($this->model->has('updatedAt'))
		{
			$data->updated_at = date('Y-m-d H:i:s');
		}
		return $this->replace($data);
	}

	/**
	 * Replace (upsert) a record.
	 *
	 * @param object $data Data to replace.
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
	 * Set the model identifier from the last insert.
	 *
	 * @param bool $result Operation result.
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
	 * Retrieve the model id key.
	 *
	 * @return string
	 */
	protected function getModelIdKeyName(): string
	{
		return $this->model->getIdKeyName();
	}

	/**
	 * Retrieve the model id value.
	 *
	 * @param object $data Data object.
	 * @param string $idKeyName Identifier key.
	 * @return mixed
	 */
	protected function getModelIdValue(object $data, string $idKeyName): mixed
	{
		return $data->{$idKeyName} ?? null;
	}

	/**
	 * Update the status of a record.
	 *
	 * @return bool
	 */
	public function updateStatus(): bool
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
	 * Update a record.
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
	 * Prepare data for an update.
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
	 * Insert or update a record based on existence.
	 *
	 * @return bool
	 */
	public function setup(): bool
	{
		$data = $this->getData();
		return ($this->exists($data) ? $this->update() : $this->add());
	}

	/**
	 * Check existence based on result count.
	 *
	 * @param array $rows Fetched rows.
	 * @return bool
	 */
	protected function checkExistCount(array $rows): bool
	{
		if (count($rows) < 1)
		{
			return false;
		}

		$row = $rows[0];
		$idKeyName = $this->model->getIdKeyName();
		$this->model->{$idKeyName} = $row->{$idKeyName} ?? null;
		return true;
	}

	/**
	 * Determine if a record exists.
	 *
	 * @param object $data Data object.
	 * @return bool
	 */
	protected function exists(object $data): bool
	{
		$idName = $this->model->getIdKeyName();
		$id = $data->{$idName} ?? null;
		if (!isset($id))
		{
			return false;
		}

		$rows = $this->select($idName)
			->where("{$this->alias}.{$idName} = ?")
			->limit(1)
			->fetch([$id]);

		return $this->checkExistCount($rows);
	}

	/**
	 * Delete a record.
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
				$key => $id
			]);
		}

		return $this->db->delete($this->tableName, $id, $key);
	}

	/**
	 * Create a query builder for a given table.
	 *
	 * @param string $tableName Table name.
	 * @param string|null $alias Table alias.
	 * @return QueryHandler
	 */
	protected function builder(string $tableName, ?string $alias = null): QueryHandler
	{
		return new QueryHandler($tableName, $alias, $this->db);
	}

	/**
	 * Normalize data from snake_case to camelCase.
	 *
	 * @param mixed $data Raw data.
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
				$rows[] = Strings::mapToCamelCase($row);
			}
			return $rows;
		}
		elseif (is_object($data))
		{
			return Strings::mapToCamelCase($data);
		}

		return $data;
	}

	/**
	 * Append join columns (potentially as subqueries) to model fields.
	 *
	 * @param array $joins Join definitions from the model.
	 * @param array &$cols Column list to append to.
	 * @param bool $isSnakeCase Snake case flag.
	 * @return void
	 */
	protected function getJoinCols(array $joins, array &$cols, bool $isSnakeCase): void
	{
		foreach ($joins as $join)
		{
			/** @var ModelJoin $join */
			if (!$join->isMultiple())
			{
				continue;
			}

			$aggregationTarget = $join->getMultipleJoin();
			if ($aggregationTarget && (count($aggregationTarget->getFields()) > 0 || $aggregationTarget->getMultipleJoin() !== null))
			{
				$builderCallback = function($table, $alias): QueryHandler
				{
					return $this->builder($table, $alias);
				};

				// Call the main entry point of the refactored SubQueryHelper
				$subQuerySql = SubQueryHelper::setupSubQuery($join, $builderCallback, $isSnakeCase);
				if ($subQuerySql !== null)
				{
					$cols[] = [$subQuerySql]; // Wrap in array if QueryBuilder expects this for raw SQL
				}
			}
		}
	}

	/**
	  * Retrieve model fields AND generate subquery columns.
	  *
	  * @param array $joins Join definitions.
	  * @return array
	  */
	protected function getColNames(array $joins): array
	{
		$cols = [];
		$isSnakeCase = $this->model->isSnakeCase();
		$fields = $this->model->getFields();

		foreach ($fields as $field)
		{
			// Format field ensures alias.field_name or just field_name if no alias
			$cols[] = FieldHelper::formatField($field, $isSnakeCase);
		}

		// Append subquery columns generated from 'multiple' joins
		$this->getJoinCols($joins, $cols, $isSnakeCase);

		return $cols;
	}

	/**
	 * Map join definitions to an array suitable for the main QueryBuilder's ->joins() method.
	 * Excludes joins that were handled by generating a subquery via getJoinCols.
	 *
	 * @param array|null $joins Join definitions from the model.
	 * @param bool $allowFields Whether to include field lists (usually false now for main query).
	 * @return array
	 */
	protected function getMappedJoins(?array $joins = null, bool $allowFields = true): array // Return type changed to array
	{
		if (empty($joins))
		{
			return [];
		}

		$isSnakeCase = $this->model->isSnakeCase();
		$mapped = [];
		foreach ($joins as $join)
		{
			/** @var ModelJoin $join */
			if ($this->isJoinHandledBySubquery($join, $joins))
			{
				continue;
			}

			// Add regular, non-aggregated joins to the main query
			$mapped[] = [
				'table' => $join->getTableName(),
				'alias' => $join->getAlias(),
				'type' => $join->getType(),
				'on' => $join->getOn(),
				'using' => $join->getUsing(),
				'fields' => ($allowFields && !$join->isMultiple()) ? FieldHelper::formatFields($join->getFields(), $isSnakeCase) : null
			];
		}

		return $mapped;
	}

	/**
	 * Helper function to determine if a join is part of an aggregation chain
	 * that was (or should be) handled by SubQueryHelper::setupSubQuery.
	 *
	 * @param ModelJoin $join The join to check.
	 * @param array $allJoins All joins defined for the model.
	 * @return bool True if the join is part of a 'multiple' chain handled by subquery.
	 */
	private function isJoinHandledBySubquery(ModelJoin $join, array $allJoins): bool
	{
		// Check if this join itself is marked multiple and starts an aggregation
		if ($join->isMultiple())
		{
			$target = $join->getMultipleJoin();
			if ($target && (count($target->getFields()) > 0 || $target->getMultipleJoin() !== null))
			{
				return true;
			}
		}

		// Check if this join is *part of* a chain started by an earlier join
		foreach ($allJoins as $potentialStartJoin)
		{
			/** @var ModelJoin $potentialStartJoin */
			if ($potentialStartJoin === $join)
			{
				// Don't check against self
				continue;
			}

			if ($potentialStartJoin->isMultiple())
			{
				$current = $potentialStartJoin->getMultipleJoin();
				while ($current)
				{
					if ($current === $join)
					{
						// This join is found within a multiple chain started earlier
						return true;
					}
					$current = $current->getMultipleJoin();
				}
			}
		}

		// It's a regular, flat join
		return false;
	}

	/**
	 * Merge custom fields with join columns.
	 *
	 * @param mixed ...$fields Custom fields.
	 * @return array
	 */
	protected function getCustomFields(...$fields): array
	{
		if (count($fields))
		{
			$joins = $this->model->getJoins();
			$fields[] = $this->getColNames($joins);
		}
		return $fields;
	}

	/**
	 * Create a select query builder.
	 *
	 * @param mixed ...$fields Field list.
	 * @return AdapterProxy
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
	 * Generate a select SQL string.
	 *
	 * @param array|null $modifiers Modifiers.
	 * @return string
	 */
	protected function getSelectSql(?array $modifiers = null): string
	{
		return $this->select() . ' ';
	}

	/**
	 * Search records by a search term.
	 *
	 * @param string $search Search term.
	 * @return array
	 */
	public function search(string $search = ''): array
	{
		return $this->select()
			->where("{$this->alias}.id = ?")
			->limit(0, 20)
			->fetch([$search]);
	}

	/**
	 * Retrieve a single record by id.
	 *
	 * @param mixed $id Identifier.
	 * @return object|null
	 */
	public function get(mixed $id): ?object
	{
		return $this->select()
			->where("{$this->alias}.id = ?")
			->first([$id]);
	}

	/**
	 * Retrieve a single record by filter.
	 *
	 * @param array|object $filter Filter criteria.
	 * @return object|null
	 */
	public function getBy($filter): mixed
	{
		$params = [];
		$where = static::setFilters($filter, $params);
		return $this->select()
			->where(...$where)
			->first($params);
	}

	/**
	 * (Optional) Apply order-by conditions.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|null $modifiers Modifiers.
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null): void
	{
		// Implement order-by logic as needed.
	}

	/**
	 * Retrieve rows by filter, limit, and modifiers.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Limit count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function getRows($filter = null, $offset = null, $limit = null, ?array $modifiers = null): object
	{
		$params = [];
		$where = static::getWhere($params, $filter, $modifiers);
		$sql = $this->select()
			->where(...$where)
			->limit($offset, $limit);

		$this->setOrderBy($sql, $modifiers);

		$rows = $sql->fetch($params);
		return (object)[ 'rows' => $rows];
	}

	/**
	 * Set up filters using the Filter helper.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array &$params Parameter array.
	 * @return array
	 */
	protected static function setFilters($filter = null, array &$params = []): array
	{
		return Filter::setup($filter, $params);
	}

	/**
	 * Allow modifiers to adjust where clauses.
	 *
	 * @param array &$where Where clauses.
	 * @param array|null $modifiers Modifiers.
	 * @param array &$params Parameter array.
	 * @param mixed $filter Filter criteria.
	 * @return void
	 */
	protected static function setModifiers(array &$where = [], ?array $modifiers = null, array &$params = [], mixed $filter = null): void
	{
		// Implement modifier logic if needed.
	}

	/**
	 * Build where clauses using filters and modifiers.
	 *
	 * @param array &$params Parameter array.
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $modifiers Modifiers.
	 * @return array
	 */
	protected static function getWhere(array &$params, $filter, ?array $modifiers = null): array
	{
		$where = static::setFilters($filter, $params);
		static::setModifiers($where, $modifiers, $params, $filter);
		return $where;
	}

	/**
	 * Create a where-based query.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array &$params Parameter array.
	 * @param array|null $modifiers Modifiers.
	 * @return AdapterProxy
	 */
	public function where($filter, array &$params, ?array $modifiers = null): AdapterProxy
	{
		$where = static::getWhere($params, $filter, $modifiers);
		return $this->select()->where(...$where);
	}

	/**
	 * Retrieve all records by filter.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $count Limit count.
	 * @param array|null $modifiers Modifiers.
	 * @return array
	 */
	public function all($filter = null, $offset = null, $count = null, ?array $modifiers = null): array
	{
		$params = [];
		return $this->where($filter, $params, $modifiers)
			->limit($offset, $count)
			->fetch($params);
	}

	/**
	 * Execute a callback on a query builder.
	 *
	 * @param callable $callBack Callback function.
	 * @param object $sql Query builder instance.
	 * @param array &$params Parameter array.
	 * @return void
	 */
	public function callBack(callable $callBack, object $sql, array &$params = []): void
	{
		if ($callBack)
		{
			call_user_func_array($callBack, [$sql, $params]);
		}
	}

	/**
	 * Find a single record using a callback.
	 *
	 * @param callable $callBack Callback function.
	 * @return mixed
	 */
	public function find(callable $callBack)
	{
		$params = [];
		$sql = $this->select();
		$this->callBack($callBack, $sql, $params);
		return $sql->first($params);
	}

	/**
	 * Find multiple records using a callback.
	 *
	 * @param callable $callBack Callback function.
	 * @return array|bool
	 */
	public function findAll(callable $callBack)
	{
		$params = [];
		$sql = $this->select();
		$this->callBack($callBack, $sql, $params);
		return $sql->fetch($params);
	}

	/**
	 * Retrieve a row count.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function count($filter = null, ?array $modifiers = null): object
	{
		$params = [];
		$where = self::getWhere($params, $filter, $modifiers);
		return $this->select([['COUNT(*)'], 'count'])->where(...$where)->first($params);
	}
}