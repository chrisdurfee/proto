<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Models\Model;

/**
 * Class Limit
 *
 * Provides methods to handle pagination and limits in SQL queries.
 *
 * @package Proto\Storage
 */
class Limit
{
	/**
	 * Sets the limit and offset for the SQL query.
	 *
	 * @param object $sql Query builder instance.
	 * @param array &$params Parameter array.
	 * @param Model $model Model instance.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Limit count.
	 * @param array|null $modifiers Modifiers.
	 * @return void
	 */
	public static function add(
		object $sql,
		array &$params,
		Model $model,
		?int $offset = null,
		?int $limit = null,
		?array $modifiers = null
	): void
	{
		// Check for 'since' parameter for fetching newer records
		$since = $modifiers['since'] ?? null;
		if ($since === '')
		{
			$since = null;
		}

		if ($since !== null)
		{
			self::since($sql, $params, $model, $limit, $since, $modifiers);
			return;
		}

		// Cursor-based pagination support: when a cursor is provided, use keyset pagination
		$cursor = $modifiers['cursor'] ?? null;
		if ($cursor === '')
		{
			$cursor = null;
		}

		if ($cursor !== null)
		{
			self::cursor($sql, $params, $model, $limit, $cursor, $modifiers);
		}
		else
		{
			self::limit($sql, $offset, $limit);
		}
	}

	/**
	 * Sets the limit and since marker for fetching newer records.
	 *
	 * @param object $sql Query builder instance.
	 * @param array &$params Parameter array.
	 * @param Model $model Model instance.
	 * @param int|null $limit Limit count.
	 * @param mixed $since Since value (fetch records after this ID).
	 * @param array|null $modifiers Query modifiers.
	 * @return void
	 */
	protected static function since(
		object $sql,
		array &$params,
		Model $model,
		?int $limit = null,
		int|string|null $since = null,
		?array $modifiers = null
	): void
	{
		// Determine ID column and add a keyset condition for newer records
		$qualifiedId = self::getCursorColumnName($model);

		// For 'since', we always want records AFTER (greater than) the marker
		// regardless of sort direction
		$dir = self::getOrderByDirection($modifiers);
		$operator = ($dir === 'DESC') ? '>' : '>';
		$sql->where("{$qualifiedId} {$operator} ?");
		$params[] = $since;

		$rowCount = $limit;
		if ($rowCount !== null)
		{
			$sql->limit((int)$rowCount);
		}
	}

	/**
	 * Sets the limit and cursor for the SQL query.
	 *
	 * Supports compound keyset pagination when multiple orderBy columns
	 * are specified. The cursor encodes values for all sort columns plus
	 * the ID tiebreaker, enabling correct pagination across multi-column sorts.
	 *
	 * @param object $sql Query builder instance.
	 * @param array &$params Parameter array.
	 * @param Model $model Model instance.
	 * @param int|null $limit Limit count.
	 * @param mixed $cursor Cursor value.
	 * @param array|null $modifiers Query modifiers.
	 * @return void
	 */
	protected static function cursor(
		object $sql,
		array &$params,
		Model $model,
		?int $limit = null,
		int|string|null $cursor = null,
		?array $modifiers = null
	): void
	{
		$qualifiedId = self::getCursorColumnName($model);
		$isSnakeCase = $model->isSnakeCase();
		$idField = ModifierUtil::prepareField($model->getIdKeyName(), $isSnakeCase);
		$orderByColumns = self::getOrderByColumns($modifiers, $model);

		// Check if any orderBy column is not the ID
		$hasNonIdColumn = false;
		$idAlreadyInOrderBy = false;
		foreach ($orderByColumns as $col)
		{
			if ($col['resultKey'] === $idField)
			{
				$idAlreadyInOrderBy = true;
			}
			else
			{
				$hasNonIdColumn = true;
			}
		}

		if ($hasNonIdColumn)
		{
			// Multi-column keyset pagination
			$tiebreakerDir = !empty($orderByColumns) ? $orderByColumns[0]['direction'] : 'ASC';

			// Add ID tiebreaker to ORDER BY if not already present
			if (!$idAlreadyInOrderBy)
			{
				$sql->orderBy("{$qualifiedId} {$tiebreakerDir}");
				$orderByColumns[] = [
					'field' => $qualifiedId,
					'resultKey' => $idField,
					'direction' => $tiebreakerDir
				];
			}

			// Decode compound cursor
			$cursorValues = self::decodeCursor($cursor);
			if ($cursorValues !== null)
			{
				self::applyCompoundCursor($sql, $params, $orderByColumns, $cursorValues);
			}
			else
			{
				// Fallback for plain ID cursor with multi-column orderBy
				$operator = ($tiebreakerDir === 'DESC') ? '<' : '>';
				$sql->where("{$qualifiedId} {$operator} ?");
				$params[] = $cursor;
			}
		}
		else
		{
			// Simple single-column keyset pagination (ID only)
			$dir = self::getOrderByDirection($modifiers);
			$operator = ($dir === 'DESC') ? '<' : '>';
			$sql->where("{$qualifiedId} {$operator} ?");
			$params[] = $cursor;
		}

		if ($limit !== null)
		{
			$sql->limit((int)$limit);
		}
	}

	/**
	 * Extract the first orderBy direction from modifiers.
	 *
	 * @param array|null $modifiers
	 * @return string 'ASC' or 'DESC'
	 */
	protected static function getOrderByDirection(?array $modifiers = null): string
	{
		$orderBy = $modifiers['orderBy'] ?? null;
		if (!$orderBy)
		{
			return 'ASC';
		}

		$orderBy = (object)$orderBy;
		if (is_object($orderBy))
		{
			foreach ($orderBy as $field => $dir)
			{
				return (strtoupper((string)$dir) === 'DESC') ? 'DESC' : 'ASC';
			}
		}

		return 'ASC';
	}

	/**
	 * Get the cursor column name for keyset pagination.
	 *
	 * @param Model $model
	 * @return string
	 */
	protected static function getCursorColumnName(Model $model): string
	{
		$isSnakeCase = $model->isSnakeCase();
		$idKey = $model->getIdKeyName();
		$idField = ModifierUtil::prepareField($idKey, $isSnakeCase);
		return $model->getAlias() . '.' . $idField;
	}

	/**
	 * Retrieves the last cursor value from the result set.
	 *
	 * For multi-column orderBy, returns a JSON-encoded compound cursor
	 * containing values for all sort columns plus the ID tiebreaker.
	 *
	 * @param array $rows Result set rows.
	 * @param Model|string $modelOrIdKey Model instance (for compound cursor) or ID key name (legacy).
	 * @param array|null $modifiers Query modifiers.
	 * @return mixed
	 */
	public static function getLastCursor(array $rows, Model|string $modelOrIdKey, ?array $modifiers = null): mixed
	{
		if (empty($rows))
		{
			return null;
		}

		$last = end($rows);

		// Legacy signature: getLastCursor($rows, 'idKey')
		if (is_string($modelOrIdKey))
		{
			return $last->{$modelOrIdKey} ?? null;
		}

		$model = $modelOrIdKey;
		$isSnakeCase = $model->isSnakeCase();
		$idField = ModifierUtil::prepareField($model->getIdKeyName(), $isSnakeCase);
		$orderByColumns = self::getOrderByColumns($modifiers, $model);

		// Check if any non-ID column is in orderBy
		$hasNonIdCol = false;
		foreach ($orderByColumns as $col)
		{
			if ($col['resultKey'] !== $idField)
			{
				$hasNonIdCol = true;
				break;
			}
		}

		// Simple cursor: return plain ID value
		if (!$hasNonIdCol)
		{
			return $last->{$idField} ?? null;
		}

		// Compound cursor: encode all sort column values + ID
		$cursor = [];
		foreach ($orderByColumns as $col)
		{
			$cursor[$col['resultKey']] = $last->{$col['resultKey']} ?? null;
		}

		if (!isset($cursor[$idField]))
		{
			$cursor[$idField] = $last->{$idField} ?? null;
		}

		return json_encode($cursor);
	}

	/**
	 * Extract orderBy column definitions from modifiers.
	 *
	 * @param array|null $modifiers Query modifiers.
	 * @param Model $model Model instance.
	 * @return array Array of ['field' => qualified name, 'resultKey' => property name, 'direction' => ASC|DESC]
	 */
	protected static function getOrderByColumns(?array $modifiers, Model $model): array
	{
		$orderBy = $modifiers['orderBy'] ?? null;
		if (empty($orderBy))
		{
			return [];
		}

		$isSnakeCase = $model->isSnakeCase();
		$alias = $model->getAlias();
		$columns = [];

		foreach ((array)$orderBy as $rawField => $rawDir)
		{
			$field = ModifierUtil::prepareField((string)$rawField, $isSnakeCase);
			if ($field === '')
			{
				continue;
			}

			$direction = strtoupper((string)$rawDir) === 'DESC' ? 'DESC' : 'ASC';
			$qualifiedField = $alias ? "{$alias}.{$field}" : $field;

			$columns[] = [
				'field' => $qualifiedField,
				'resultKey' => $field,
				'direction' => $direction
			];
		}

		return $columns;
	}

	/**
	 * Decode a cursor value into an associative array.
	 *
	 * Returns an associative array for compound cursors (JSON), or null
	 * for plain ID cursors.
	 *
	 * @param int|string|null $cursor Raw cursor value.
	 * @return array|null Decoded cursor values, or null for plain ID.
	 */
	protected static function decodeCursor(int|string|null $cursor): ?array
	{
		if ($cursor === null)
		{
			return null;
		}

		if (is_string($cursor))
		{
			$decoded = json_decode($cursor, true);
			if (is_array($decoded) && !empty($decoded))
			{
				return $decoded;
			}
		}

		return null;
	}

	/**
	 * Apply a compound keyset cursor condition for multi-column ordering.
	 *
	 * Builds a WHERE clause equivalent to row-value comparison:
	 * (col1, col2, id) > (val1, val2, valId)
	 *
	 * Expands to:
	 * (col1 > val1) OR (col1 = val1 AND col2 > val2)
	 * OR (col1 = val1 AND col2 = val2 AND id > valId)
	 *
	 * @param object $sql Query builder instance.
	 * @param array &$params Parameter array.
	 * @param array $orderByColumns Column definitions from getOrderByColumns().
	 * @param array $cursorValues Decoded cursor values keyed by resultKey.
	 * @return void
	 */
	protected static function applyCompoundCursor(
		object $sql,
		array &$params,
		array $orderByColumns,
		array $cursorValues
	): void
	{
		$conditions = [];
		$count = count($orderByColumns);

		for ($i = 0; $i < $count; $i++)
		{
			$parts = [];

			// All preceding columns must be equal
			for ($j = 0; $j < $i; $j++)
			{
				$col = $orderByColumns[$j];
				$value = $cursorValues[$col['resultKey']] ?? null;

				if ($value === null)
				{
					$parts[] = "{$col['field']} IS NULL";
				}
				else
				{
					$parts[] = "{$col['field']} = ?";
					$params[] = $value;
				}
			}

			// Current column uses > or < based on its sort direction
			$col = $orderByColumns[$i];
			$value = $cursorValues[$col['resultKey']] ?? null;
			$operator = ($col['direction'] === 'DESC') ? '<' : '>';

			if ($value === null)
			{
				continue;
			}

			$parts[] = "{$col['field']} {$operator} ?";
			$params[] = $value;

			$conditions[] = '(' . implode(' AND ', $parts) . ')';
		}

		if (!empty($conditions))
		{
			$sql->where('(' . implode(' OR ', $conditions) . ')');
		}
	}

	/**
	 * Adds pagination limits to the SQL query.
	 *
	 * @param object $sql Query builder instance.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Limit count.
	 * @return void
	 */
	protected static function limit(object $sql, ?int $offset = null, ?int $limit = null): void
	{
		if ($offset !== null && $limit !== null)
		{
			$sql->limit($offset, $limit);
			return;
		}

		if ($limit !== null)
		{
			$sql->limit($limit);
		}
	}
}