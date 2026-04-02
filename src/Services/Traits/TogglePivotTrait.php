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
}
