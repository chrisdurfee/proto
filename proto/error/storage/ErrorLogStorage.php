<?php declare(strict_types=1);
namespace Proto\Error\Storage;

use Proto\Storage\Storage;

/**
 * ErrorLogStorage
 *
 * This will handle the storage for the error log.
 *
 * @package Proto\Storage
 */
class ErrorLogStorage extends Storage
{
	/**
	 * This will set the getRows order by.
	 *
	 * @param object $sql
	 * @param array|null $modifiers
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null): void
	{
		$sql->orderBy("e.created_at DESC");
	}

	/**
	 * This will allow the where to be modified by modifiers.
	 *
	 * @param array $where
	 * @param array|null $modifiers
	 * @param array $params
	 * @param array $filter
	 * @return void
	 */
	protected static function setModifiers(array &$where = [], ?array $modifiers = null, array &$params = [], $filter = null): void
	{
		$custom = $modifiers['custom'] ?? '';
		if ($custom)
		{
			array_push($params, $custom);
			$where[] = "e.env = ?";
		}

		$term = $modifiers['search'] ?? '';
		if (empty($term) === false)
		{
			$params[] = "%{$term}%";
			$where[] = "e.error_message LIKE ?";
		}
	}

	/**
	 * This will update the item resolved status.
	 *
	 * @return bool
	 */
	public function updateResolved(): bool
	{
		$data = $this->getUpdateData();
		$dateTime = date('Y-m-d H:i:s');

		return $this->db->update($this->tableName, [
			'id' => $data->id,
			'resolved' => $data->resolved,
			'updated_at' => $dateTime
		]);
	}
}