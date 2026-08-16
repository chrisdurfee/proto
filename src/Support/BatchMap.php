<?php declare(strict_types=1);
namespace Proto\Support;

use Proto\Utils\Strings;

/**
 * BatchMap
 *
 * Standalone batch-enrichment helpers for services and controllers.
 * Avoids N+1 lookups by fetching related rows with a single IN query.
 *
 * @package Proto\Support
 */
class BatchMap
{
	/**
	 * Batch-fetch a single field from a related model and map it onto rows.
	 *
	 * @param array $rows
	 * @param string $modelClass
	 * @param string $foreignKey
	 * @param string $valueField
	 * @param string $targetField
	 * @param mixed $default
	 * @param array $extraFilter
	 * @param string $sourceKey
	 * @return void
	 */
	public static function field(
		array &$rows,
		string $modelClass,
		string $foreignKey,
		string $valueField,
		string $targetField,
		mixed $default = null,
		array $extraFilter = [],
		string $sourceKey = 'id'
	): void
	{
		foreach ($rows as &$row)
		{
			$row->$targetField = $default;
		}
		unset($row);

		$results = self::fetch($rows, $modelClass, $foreignKey, $extraFilter, $sourceKey);
		if ($results === null)
		{
			return;
		}

		$map = [];
		foreach ($results as $r)
		{
			$key = self::value($r, $foreignKey);
			if ($key === null)
			{
				continue;
			}

			$map[$key] = self::value($r, $valueField);
		}

		foreach ($rows as &$row)
		{
			$key = self::value($row, $sourceKey);
			if ($key !== null && isset($map[$key]))
			{
				$row->$targetField = $map[$key];
			}
		}
		unset($row);
	}

	/**
	 * Batch-check existence of related records and set a boolean flag.
	 *
	 * @param array $rows
	 * @param string $modelClass
	 * @param string $foreignKey
	 * @param string $targetField
	 * @param array $extraFilter
	 * @param string $sourceKey
	 * @return void
	 */
	public static function exists(
		array &$rows,
		string $modelClass,
		string $foreignKey,
		string $targetField,
		array $extraFilter = [],
		string $sourceKey = 'id'
	): void
	{
		foreach ($rows as &$row)
		{
			$row->$targetField = false;
		}
		unset($row);

		$results = self::fetch($rows, $modelClass, $foreignKey, $extraFilter, $sourceKey);
		if ($results === null)
		{
			return;
		}

		$set = [];
		foreach ($results as $r)
		{
			$key = self::value($r, $foreignKey);
			if ($key !== null)
			{
				$set[$key] = true;
			}
		}

		foreach ($rows as &$row)
		{
			$key = self::value($row, $sourceKey);
			if ($key !== null)
			{
				$row->$targetField = isset($set[$key]);
			}
		}
		unset($row);
	}

	/**
	 * Batch-count related records per row.
	 *
	 * @param array $rows
	 * @param string $modelClass
	 * @param string $foreignKey
	 * @param string $targetField
	 * @param array $extraFilter
	 * @param string $sourceKey
	 * @return void
	 */
	public static function count(
		array &$rows,
		string $modelClass,
		string $foreignKey,
		string $targetField,
		array $extraFilter = [],
		string $sourceKey = 'id'
	): void
	{
		foreach ($rows as &$row)
		{
			$row->$targetField = 0;
		}
		unset($row);

		$ids = array_unique(array_filter(
			array_map(fn($r) => self::value($r, $sourceKey), $rows)
		));
		if ($ids === [] || !is_callable([$modelClass, 'countGroupedBy']))
		{
			return;
		}

		$counts = $modelClass::countGroupedBy($foreignKey, array_values($ids), $extraFilter);
		if ($counts === [])
		{
			return;
		}

		foreach ($rows as &$row)
		{
			$key = self::value($row, $sourceKey);
			if ($key !== null && isset($counts[$key]))
			{
				$row->$targetField = $counts[$key];
			}
		}
		unset($row);
	}

	/**
	 * @param array $rows
	 * @param string $modelClass
	 * @param string $foreignKey
	 * @param array $extraFilter
	 * @param string $sourceKey
	 * @return array|null
	 */
	protected static function fetch(
		array $rows,
		string $modelClass,
		string $foreignKey,
		array $extraFilter,
		string $sourceKey
	): ?array
	{
		if (empty($rows))
		{
			return null;
		}

		$ids = array_unique(array_filter(
			array_map(fn($r) => self::value($r, $sourceKey), $rows)
		));
		if (empty($ids))
		{
			return null;
		}

		$filter = array_merge(
			[[$foreignKey, 'IN', array_values($ids)]],
			$extraFilter
		);

		if (is_callable([$modelClass, 'fetchWhereWithoutJoins']))
		{
			$results = $modelClass::fetchWhereWithoutJoins($filter);
			return $results === [] ? null : $results;
		}

		return $modelClass::fetchWhere($filter);
	}

	/**
	 * Read a row property without TypeErroring on missing keys.
	 *
	 * Accepts camelCase or snake_case so raw storage rows and
	 * convertRows() output both map.
	 *
	 * @param object $row
	 * @param string $key
	 * @return mixed
	 */
	public static function value(object $row, string $key): mixed
	{
		if (isset($row->$key))
		{
			return $row->$key;
		}

		$snake = Strings::snakeCase($key);
		if ($snake !== $key && isset($row->$snake))
		{
			return $row->$snake;
		}

		$camel = Strings::camelCase($key);
		if ($camel !== $key && isset($row->$camel))
		{
			return $row->$camel;
		}

		return null;
	}
}
