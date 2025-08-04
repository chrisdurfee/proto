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
	 * This will allow the where to be modified by modifiers.
	 *
	 * @param array $where
	 * @param array|null $modifiers
	 * @param array $params
	 * @param mixed $filter
	 * @return void
	 */
	protected static function setModifiers(array &$where = [], ?array $modifiers = null, array &$params = [], mixed $filter = null): void
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