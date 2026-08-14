<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * TogglePivotTrait
 *
 * Generic pivot toggle pattern for services.
 *
 * Handles the existence-check toggle: if pivot exists → delete it;
 * if not → create it. Used for bookmarks, favorites, follows, etc.
 *
 * @package Proto\Services\Traits
 */
trait TogglePivotTrait
{
	/**
	 * Toggle a pivot record's existence.
	 *
	 * @param string $modelClass The pivot model class.
	 * @param array $filter Associative array of filter conditions (e.g., ['userId' => 1, 'itemId' => 5]).
	 * @param array $extraData Additional data to set when creating (optional).
	 * @return object {active: bool, record: ?object}
	 */
	protected function togglePivot(string $modelClass, array $filter, array $extraData = []): object
	{
		$existing = $modelClass::getBy($filter);
		if ($existing)
		{
			$modelClass::remove($existing->id);
			return (object)['active' => false, 'record' => null];
		}

		$data = array_merge($filter, $extraData);
		$record = new $modelClass((object)$data);
		$record->add();
		return (object)['active' => true, 'record' => $record];
	}

	/**
	 * Toggle a pivot row and atomically adjust a counter on the parent row.
	 *
	 * @param string $pivotModelClass Pivot model class.
	 * @param array<string, mixed> $pivotKey Unique key on the pivot model.
	 * @param string $parentModelClass Parent model carrying the counter.
	 * @param int $parentId Parent row id.
	 * @param string $counterField Counter column on the parent model.
	 * @param array<string, mixed> $extraData Extra fields when creating the pivot.
	 * @return object {active: bool, record: ?object}
	 */
	protected function togglePivotWithCounter(
		string $pivotModelClass,
		array $pivotKey,
		string $parentModelClass,
		int $parentId,
		string $counterField,
		array $extraData = []
	): object
	{
		$result = $this->togglePivot($pivotModelClass, $pivotKey, $extraData);

		if ($result->active)
		{
			$parentModelClass::atomicIncrement($parentId, $counterField);
		}
		else
		{
			$parentModelClass::atomicDecrement($parentId, $counterField);
		}

		return $result;
	}
}
