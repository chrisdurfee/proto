<?php declare(strict_types=1);

namespace Proto\Controllers\Traits;

use Proto\Support\BatchMap;

/**
 * BatchEnrichmentTrait
 *
 * Provides helpers for batch-fetching related data and mapping it onto result rows.
 * Use these methods inside enrichRows() to avoid N+1 queries.
 *
 * @package Proto\Controllers\Traits
 */
trait BatchEnrichmentTrait
{
	/**
	 * Batch-fetch a single field from a related model and map it onto rows.
	 *
	 * @param array $rows Result rows to enrich.
	 * @param string $modelClass Related model class.
	 * @param string $foreignKey Field on the related model matching row IDs.
	 * @param string $valueField Field on the related model to extract.
	 * @param string $targetField Field name to set on each row.
	 * @param mixed $default Default value when no match found.
	 * @param array $extraFilter Additional filter conditions.
	 * @param string $sourceKey Field on rows to match against (default: 'id').
	 * @return void
	 */
	protected function batchMapField(
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

		if (empty($rows))
		{
			return;
		}

		$ids = array_unique(array_filter(
			array_map(fn($r) => $this->rowValue($r, $sourceKey), $rows)
		));
		if (empty($ids))
		{
			return;
		}

		$filter = array_merge(
			[[$foreignKey, 'IN', array_values($ids)]],
			$extraFilter
		);
		$results = $this->batchFetchRelated($modelClass, $filter);
		if (!$results)
		{
			return;
		}

		$map = [];
		foreach ($results as $r)
		{
			$key = $this->rowValue($r, $foreignKey);
			if ($key === null)
			{
				continue;
			}

			$map[$key] = $this->rowValue($r, $valueField);
		}

		foreach ($rows as &$row)
		{
			$key = $this->rowValue($row, $sourceKey);
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
	 * @param array $rows Result rows to enrich.
	 * @param string $modelClass Related model class.
	 * @param string $foreignKey Field on the related model matching row IDs.
	 * @param string $targetField Boolean field name to set on each row.
	 * @param array $extraFilter Additional filter conditions.
	 * @param string $sourceKey Field on rows to match against (default: 'id').
	 * @return void
	 */
	protected function batchMapExists(
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

		if (empty($rows))
		{
			return;
		}

		$ids = array_unique(array_filter(
			array_map(fn($r) => $this->rowValue($r, $sourceKey), $rows)
		));
		if (empty($ids))
		{
			return;
		}

		$filter = array_merge(
			[[$foreignKey, 'IN', array_values($ids)]],
			$extraFilter
		);
		$results = $this->batchFetchRelated($modelClass, $filter);
		if (!$results)
		{
			return;
		}

		$set = [];
		foreach ($results as $r)
		{
			$key = $this->rowValue($r, $foreignKey);
			if ($key !== null)
			{
				$set[$key] = true;
			}
		}

		foreach ($rows as &$row)
		{
			$key = $this->rowValue($row, $sourceKey);
			if ($key !== null)
			{
				$row->$targetField = isset($set[$key]);
			}
		}
		unset($row);
	}

	/**
	 * Batch-count related records per row and set an integer field.
	 *
	 * @param array $rows Result rows to enrich.
	 * @param string $modelClass Related model class.
	 * @param string $foreignKey Field on the related model matching row IDs.
	 * @param string $targetField Integer field name to set on each row.
	 * @param array $extraFilter Additional filter conditions.
	 * @param string $sourceKey Field on rows to match against (default: 'id').
	 * @return void
	 */
	protected function batchMapCount(
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

		if (empty($rows))
		{
			return;
		}

		$ids = array_unique(array_filter(
			array_map(fn($r) => $this->rowValue($r, $sourceKey), $rows)
		));
		if (empty($ids))
		{
			return;
		}

		$counts = is_callable([$modelClass, 'countGroupedBy'])
			? $modelClass::countGroupedBy($foreignKey, array_values($ids), $extraFilter)
			: [];
		if ($counts === [])
		{
			return;
		}

		foreach ($rows as &$row)
		{
			$key = $this->rowValue($row, $sourceKey);
			if ($key !== null && isset($counts[$key]))
			{
				$row->$targetField = $counts[$key];
			}
		}
		unset($row);
	}

	/**
	 * Fetch related rows without eager joins so unqualified filter columns
	 * (e.g. `user_id IN (...)`) are not ambiguous against joined tables.
	 *
	 * Falls back to `fetchWhere()` when `fetchWhereWithoutJoins` is unavailable.
	 *
	 * @param string $modelClass
	 * @param array $filter
	 * @return array|null
	 */
	protected function batchFetchRelated(string $modelClass, array $filter): ?array
	{
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
	protected function rowValue(object $row, string $key): mixed
	{
		return BatchMap::value($row, $key);
	}
}
