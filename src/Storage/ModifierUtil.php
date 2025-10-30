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
	public static function addDateModifier(object $dates, array &$where, array &$params, bool $isSnakeCase = true, string $alias = ''): void
	{
		$field = $dates->field ?? 'createdAt';
		$field = self::prepareField($field, $isSnakeCase);
		if ($alias !== '')
		{
			$field = $alias . '.' . $field;
		}

		$start = $dates->start ?? '';
		$start = (str_contains($start, ':')) ? $start : $start . ' 00:00:00';
		$params[] = $start;

		$end = $dates->end ?? '';
		$end = (str_contains($end, ':')) ? $end : $end . ' 23:59:59';
		$params[] = $end;
		$where[] = "($field BETWEEN ? AND ?)";
	}

	/**
	 * Add a search modifier.
	 *
	 * @param string $search
	 * @param array $where
	 * @param array $params
	 * @param bool $isSnakeCase
	 * @param string $alias
	 * @param array $searchableFields
	 * @return void
	 */
	public static function addSearchModifier(
		string $search,
		array &$where,
		array &$params,
		bool $isSnakeCase = true,
		string $alias = '',
		array $searchableFields = []
	): void
	{
		$term =  $search ?? '';
		$fields = !empty($searchableFields) ? $searchableFields : [];
		if ($term === '' || empty($fields))
		{
			return;
		}

		$term = '%' . strtolower($term) . '%';
		$searchConditions = [];
		foreach ($fields as $field)
		{
			// Check if the field is an array (e.g., ["CONCAT(last_name, ' ', first_name)"])
			if (is_array($field))
			{
				$field = $field[0];
			}
			else
			{
				$field = self::prepareField($field, $isSnakeCase);
				if ($alias !== '')
				{
					$field = $alias . '.' . $field;
				}
			}

			$searchConditions[] = "LOWER($field) LIKE ?";
			$params[] = $term;
		}

		if (!empty($searchConditions))
		{
			$where[] = '(' . implode(' OR ', $searchConditions) . ')';
		}
	}

	/**
	 * Add a modifier to exclude soft-deleted records.
	 *
	 * @param array $where Where clause array.
	 * @param array $params Parameter array.
	 * @param bool $isSnakeCase Whether to convert field names to snake_case.
	 * @param string $alias Optional table alias.
	 * @return void
	 */
	public static function addDeletedAtModifier(array &$where, array &$params, bool $isSnakeCase = true, string $alias = ''): void
	{
		$field = self::prepareField('deletedAt', $isSnakeCase);
		if ($alias !== '')
		{
			$field = $alias . '.' . $field;
		}

		$where[] = "($field IS NULL)";
	}

	/**
	 * Apply order-by conditions.
	 *
	 * @param object $sql Query builder instance.
	 * @param array|object|null $orderBy Order-by conditions.
	 * @param bool $isSnakeCase Whether to convert field names to snake_case.
	 * @param string $alias Optional table alias.
	 * @return void
	 */
	public static function setOrderBy(object $sql, array|object|null $orderBy, bool $isSnakeCase = true, string $alias = ''): void
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

			if ($alias !== '')
			{
				$field = $alias . '.' . $field;
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
	 * @param bool $isSnakeCase Whether to convert field names to snake_case.
	 * @param string $alias Optional table alias.
	 * @return void
	 */
	public static function setGroupBy(object $sql, ?array $groupBy, bool $isSnakeCase = true, string $alias = ''): void
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

			if ($alias !== '')
			{
				$field = $alias . '.' . $field;
			}
			$fields[] = $field;
		}

		$sql->groupBy(...$fields);
	}
}
