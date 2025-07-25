<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Utils\Strings;
use Proto\Utils\Sanitize;

/**
 * ModifierUtil
 *
 * Provides utility functions for modifying data.
 *
 * @package Proto\Storage
 */
class ModifierUtil
{
	/**
	 * Prepare a field name for use in queries.
	 *
	 * @param string $field Field name.
	 * @param bool $isSnakeCase Whether to convert to snake_case.
	 * @return string
	 */
	public static function prepareField(string $field, bool $isSnakeCase = true): string
	{
		if ($isSnakeCase)
		{
			$field = Strings::snakeCase($field);
		}
		return Sanitize::cleanColumn($field);
	}

	/**
	 * Add a date range modifier.
	 *
	 * @param object $dates Date range object.
	 * @param array $where Where clause array.
	 * @param array $params Parameter array.
	 * @param bool $isSnakeCase Whether to convert field names to snake_case.
	 * @return void
	 */
	public static function addDateModifier(object $dates, array &$where, array &$params, bool $isSnakeCase = true): void
	{
		$field = $dates->field ?? 'createdAt';
		$field = self::prepareField($field, $isSnakeCase);

		$params[] = $dates->start ?? '';
		$params[] = $dates->end ?? '';
		$where[] = "($field BETWEEN ? AND ?)";
	}

	/**
	 * Apply order-by conditions.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|object|null $orderBy Order-by conditions.
	 * @param bool $isSnakeCase Whether to convert field names to snake_case.
	 * @return void
	 */
	public static function setOrderBy(object $sql, array|object|null $orderBy, bool $isSnakeCase = true): void
	{
		if (empty($orderBy))
		{
			return;
		}

		foreach ($orderBy as $rawField  => $rawDir)
		{
			$field = self::prepareField($rawField, $isSnakeCase);
			if ($field === '')
			{
				// skip empty or entirely‐stripped names
				continue;
			}

			$direction = strtoupper((string)$rawDir) === 'DESC' ? 'DESC' : 'ASC';
			$sql->orderBy("{$field} {$direction}");
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
	public static function setGroupBy(object $sql, ?array $groupBy, bool $isSnakeCase = true): void
	{
		if (empty($groupBy))
		{
			return;
		}

		$fields = [];
		foreach ($groupBy as $rawField)
		{
			$field = self::prepareField($rawField, $isSnakeCase);
			if ($field === '')
			{
				// skip empty or entirely‐stripped names
				continue;
			}
			$fields[] = $field;
		}

		$sql->groupBy(...$fields);
	}
}
