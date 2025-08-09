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
		// Cursor-based pagination support: when a cursor is provided, use keyset pagination
		$cursor = $modifiers['cursor'] ?? null;
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
	 * Sets the limit and cursor for the SQL query.
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
		mixed $cursor = null,
		?array $modifiers = null
	): void
	{
		// Determine ID column and add a keyset condition
		$qualifiedId = self::getCursorColumnName($model);

		// Use operator based on existing orderBy direction (ASC => ">", DESC => "<")
		$dir = self::getOrderByDirection($modifiers);
		$operator = ($dir === 'DESC') ? '<' : '>';
		$sql->where("{$qualifiedId} {$operator} ?");
		$params[] = $cursor;

		$rowCount = $limit;
		if ($rowCount !== null)
		{
			// One-arg limit means row count in our Query builder
			$sql->limit((int)$rowCount);
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
	 * @param array $rows Result set rows.
	 * @param string $idKey ID key name.
	 * @return mixed
	 */
	public static function getLastCursor(array $rows, string $idKey): mixed
	{
		if (empty($rows))
		{
			return null;
		}

		$last = end($rows);
		return $last->{$idKey} ?? null;
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