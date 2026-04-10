<?php declare(strict_types=1);

namespace Proto\Controllers\Traits;

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
			array_map(fn($r) => $r->$sourceKey ?? null, $rows)
		));
		if (empty($ids))
		{
			return;
		}

		$filter = array_merge(
			[[$foreignKey, 'IN', array_values($ids)]],
			$extraFilter
		);
		$results = $modelClass::fetchWhere($filter);
		if (!$results)
		{
			return;
		}

		$map = [];
		foreach ($results as $r)
		{
			$map[$r->$foreignKey] = $r->$valueField;
		}

		foreach ($rows as &$row)
		{
			$key = $row->$sourceKey ?? null;
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
			array_map(fn($r) => $r->$sourceKey ?? null, $rows)
		));
		if (empty($ids))
		{
			return;
		}

		$filter = array_merge(
			[[$foreignKey, 'IN', array_values($ids)]],
			$extraFilter
		);
		$results = $modelClass::fetchWhere($filter);
		if (!$results)
		{
			return;
		}

		$set = [];
		foreach ($results as $r)
		{
			$set[$r->$foreignKey] = true;
		}

		foreach ($rows as &$row)
		{
			$key = $row->$sourceKey ?? null;
			if ($key !== null)
			{
				$row->$targetField = isset($set[$key]);
			}
		}
		unset($row);
	}
}
