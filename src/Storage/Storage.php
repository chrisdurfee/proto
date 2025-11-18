<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Models\Model;
use Proto\Models\Joins\ModelJoin;
use Proto\Database\Database;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Storage\Helpers\FieldHelper;
use Proto\Storage\Helpers\SubQueryHelper;
use Proto\Utils\Sanitize;
use Proto\Utils\Strings;

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
class Storage extends TableStorage
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
	 * Storage constructor.
	 *
	 * @param Model $model The model instance.
	 * @param string $database The database adapter class.
	 */
	public function __construct(
		protected Model $model,
		string $database = Database::class
	)
	{
		parent::__construct($database);
		$this->tableName = $model->getTableName();
		$this->alias = $model->getAlias() ?: $this->tableName;
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
	 * Create a query builder for the model table.
	 *
	 * @param string|null $tableName Optional table name.
	 * @param string|null $alias Optional table alias.
	 * @return QueryHandler
	 */
	public function table(?string $tableName = null, ?string $alias = null): QueryHandler
	{
		$tableName = $tableName ?? $this->tableName;
		$alias = $alias ?? $this->alias;
		return $this->db->table($tableName, $alias);
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

		return $cols;
	}

	/**
	 * Map join definitions to an array suitable for the main QueryBuilder's ->joins() method.
	 * Now supports turning a 'multiple' ModelJoin into a subquery-join.
	 *
	 * @param array|null $joins
	 * @param bool $allowFields
	 * @return array
	 */
	protected function getMappedJoins(
		?array $joins = null,
		bool $allowFields = true
	): array
	{
		if (empty($joins))
		{
			return [];
		}

		$isSnakeCase = $this->model->isSnakeCase();
		$mapped = [];

		// 1) subquery‐joins for all multiple ModelJoins
		foreach ($joins as $join)
		{
			if (! $join->isMultiple())
			{
				continue;
			}

			$def = SubQueryHelper::getSubQueryJoinDefinition(
				$join,
				fn($table, $alias) => $this->builder($table, $alias),
				$isSnakeCase
			);

			if ($def !== null)
			{
				$mapped[] = $def;
			}
		}

		// 2) normal joins
		foreach ($joins as $join)
		{
			if ($this->isJoinHandledBySubquery($join, $joins))
			{
				continue;
			}

			$mapped[] = [
				'table' => $join->getTableName(),
				'alias' => $join->getAlias(),
				'type' => $join->getType(),
				'on' => $join->getOn(),
				'using' => $join->getUsing(),
				'fields' => ($allowFields && !$join->isMultiple())
					? FieldHelper::formatFields(
						$join->getFields(),
						$isSnakeCase
					)
					: null
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
	 * @return object
	 */
	public function select(...$fields): object
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
		$idKey = $this->model->getIdKeyName();
		return $this->select()
			->where("{$this->alias}.{$idKey} = ?")
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
		$idKey = $this->model->getIdKeyName();
		return $this->select()
			->where("{$this->alias}.{$idKey} = ?")
			->first([$id]);
	}

	/**
	 * Retrieve a single record by filter.
	 *
	 * @param array|object $filter Filter criteria.
	 * @return object|null
	 */
	public function getBy(object|array $filter): mixed
	{
		$params = [];
		$where = static::setFilters($filter, $params);
		return $this->select()
			->where(...$where)
			->first($params);
	}

	/**
	 * (Optional) Sets a custom where clause.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|null $modifiers Modifiers.
	 * @param array|null $params Parameter array.
	 * @return void
	 */
	protected function setCustomWhere(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
	}

	/**
	 * (Optional) Apply order-by conditions.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|null $modifiers Modifiers.
	 * @param array|null $params Parameter array.
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
		$orderBy = $modifiers['orderBy'] ?? null;
		if (is_object($orderBy))
		{
			$alias = $this->model->getAlias();
			$isSnakeCase = $this->model->isSnakeCase();
			ModifierUtil::setOrderBy($sql, $orderBy, $isSnakeCase, $alias);
		}
	}

	/**
	 * Apply group-by conditions.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|null $modifiers Modifiers.
	 * @param array|null $params Parameter array.
	 * @return void
	 */
	protected function setGroupBy(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
		$groupBy = $modifiers['groupBy'] ?? null;
		if (is_array($groupBy))
		{
			$alias = $this->model->getAlias();
			$isSnakeCase = $this->model->isSnakeCase();
			ModifierUtil::setGroupBy($sql, $groupBy, $isSnakeCase, $alias);
		}
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
	public function getRows(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object
	{
		$params = [];
		$sql = $this->where($filter, $params, $modifiers);

		/**
		 * This will add a limit by cursor or offset.
		 */
		Limit::add($sql, $params, $this->model, $offset, $limit, $modifiers);

		$rows = $sql->fetch($params);
		$result = [ 'rows' => $rows ];
		$this->setLastCursor($result, $rows);

		return (object)$result;
	}

	/**
	 * Sets the last cursor.
	 *
	 * @param array $result
	 * @param array $rows
	 * @return void
	 */
	protected function setLastCursor(array &$result, array $rows): void
	{
		if (!empty($rows))
		{
			$idKey = $this->model->getIdKeyName();
			$result['lastCursor'] = Limit::getLastCursor($rows, $idKey);
		}
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
	protected function setDefaultModifiers(array &$where = [], ?array $modifiers = null, array &$params = [], mixed $filter = null): void
	{
		$isSnakeCase = $this->model->isSnakeCase();
		$alias = $this->model->getAlias();

		$dates = $modifiers['dates'] ?? '';
		if (is_object($dates))
		{
			ModifierUtil::addDateModifier($dates, $where, $params, $isSnakeCase, $alias);
		}

		$searchableFields = $this->model->getSearchableFields() ?? [];
		$search = $modifiers['search'] ?? null;
		if (isset($search) && count($searchableFields) > 0)
		{
			ModifierUtil::addSearchModifier($search, $where, $params, $isSnakeCase, $alias, $searchableFields);
		}

		$hasDeletedAt = $this->model->has('deletedAt');
		$showDeleted = $modifiers['showDeleted'] ?? false;
		if ($hasDeletedAt && !$showDeleted)
		{
			ModifierUtil::addDeletedAtModifier($where, $params, $isSnakeCase, $alias);
		}

		static::setModifiers($where, $modifiers, $params, $filter);
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
	}

	/**
	 * Search within a joined table using an EXISTS subquery.
	 * This allows searching nested/aggregated data efficiently.
	 *
	 * @param string $joinAlias The alias of the join to search in (e.g., 'participants')
	 * @param array $searchFields Field names to search (e.g., ['firstName', 'lastName'])
	 * @param string $searchValue The search value
	 * @param array &$params Parameter array to append to
	 * @return string The EXISTS subquery SQL
	 */
	protected function searchByJoin(
		string $joinAlias,
		array $searchFields,
		string $searchValue,
		array &$params
	): string
	{
		$result = Helpers\JoinSearchHelper::buildSearchSubquery(
			$joinAlias,
			$searchFields,
			$searchValue,
			$this->model->getJoins(),
			$this->model->getAlias(),
			$this->model->isSnakeCase()
		);

		if ($result === null)
		{
			return '1=1'; // No-op if join not found
		}

		// Append params
		foreach ($result['params'] as $param)
		{
			$params[] = $param;
		}

		return $result['sql'];
	}

	/**
	 * Prepare a field name for use in queries.
	 *
	 * @param string $field Field name.
	 * @param bool $isSnakeCase Whether to convert to snake_case.
	 * @return string
	 */
	protected static function prepareField(string $field, bool $isSnakeCase = true): string
	{
		if ($isSnakeCase)
		{
			$field = Strings::snakeCase($field);
		}
		return Sanitize::cleanColumn($field);
	}

	/**
	 * Build where clauses using filters and modifiers.
	 *
	 * @param array|null &$params Parameter array.
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $modifiers Modifiers.
	 * @return array
	 */
	protected function getWhere(?array &$params = null, mixed $filter = null, ?array $modifiers = null): array
	{
		$where = static::setFilters($filter, $params);
		$this->setDefaultModifiers($where, $modifiers, $params, $filter);
		return $where;
	}

	/**
	 * Create a where-based query.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null &$params Parameter array.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function where(mixed $filter = null, ?array &$params = null, ?array $modifiers = null): object
	{
		/**
		 * @SuppressWarnings PHP0408,PHP0423
		*/
		$sql = $this->select();

		/**
		 * If the default params is empty, we will use the adapter proxy params
		 * for the modifiers.
		 */
		if (!isset($params))
		{
			$params =& $sql->params();
		}

		$where = $this->getWhere( $params, $filter, $modifiers);
		$sql->where(...$where);

		$this->setCustomWhere($sql, $modifiers, $params);
		$this->setOrderBy($sql, $modifiers, $params);
		$this->setGroupBy($sql, $modifiers, $params);

		return $sql;
	}

	/**
	 * Retrieve all records by filter.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Limit count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function all(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object
	{
		$params = [];
		$sql = $this->where($filter, $params, $modifiers);

		/**
		 * This will add a limit by cursor or offset.
		 */
		Limit::add($sql, $params, $this->model, $offset, $limit, $modifiers);

		$rows = $sql->fetch($params);
		$result = [ 'rows' => $rows ];
		$this->setLastCursor($result, $rows);

		return (object)$result;
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
	 * Find a single record using a callback or return the query builder
	 * if no callback is provided.
	 *
	 * @param callable|null $callBack Callback function.
	 * @return mixed
	 */
	public function find(?callable $callBack = null): mixed
	{
		$params = [];
		$sql = $this->select()
			->limit(1);

		if ($callBack)
		{
			$this->callBack($callBack, $sql, $params);
			return $sql->first($params);
		}

		return $sql;
	}

	/**
	 * Find multiple records using a callback.
	 *
	 * @param callable|null $callBack Callback function.
	 * @return array|bool|object
	 */
	public function findAll(?callable $callBack = null): mixed
	{
		$params = [];
		$sql = $this->select();

		if ($callBack)
		{
			$this->callBack($callBack, $sql, $params);
			return $sql->fetch($params);
		}

		return $sql;
	}

	/**
	 * Retrieve a row count.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function count(mixed $filter = null, ?array $modifiers = null): object
	{
		$params = [];
		$where = $this->getWhere($params, $filter, $modifiers);
		$sql = $this->select([['COUNT(*)'], 'count'])->where(...$where);

		$this->setCustomWhere($sql, $modifiers, $params);
		$this->setOrderBy($sql, $modifiers, $params);
		$this->setGroupBy($sql, $modifiers, $params);

		return $sql->first($params);
	}
}