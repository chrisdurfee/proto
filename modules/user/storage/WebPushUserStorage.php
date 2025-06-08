<?php declare(strict_types=1);
namespace Modules\User\Storage;

use Proto\Storage\Storage;

/**
 * WebPushUserStorage
 *
 * Handles the storage operations for web push users.
 *
 * @package Modules\User\Storage
 */
class WebPushUserStorage extends Storage
{
	/**
	 * This will get the user by ID.
	 *
	 * @param mixed $userId
	 * @param bool $limit
	 * @return array|null
	 */
	public function getByUser(mixed $userId, bool $limit = false): ?array
	{
		if (!isset($userId))
		{
			return null;
		}

		$sql = $this->select()
			->where(
				"user_id = ?",
				"status = 'active'"
			);

		if ($limit)
		{
			$sql->limit(1);
		}

		$rows = $this->fetch($sql, [$userId]);
		return $rows ?? null;
	}

	/**
	 * This will check if the row exists.
	 *
	 * @param mixed $data
	 * @return bool
	 */
	protected function exists(object $data): bool
	{
		$rows = $this->select('id')
			->where('user_id = ?', 'endpoint = ?')
			->limit(1)
			->fetch([$data->userId, $data->endpoint]);

		return $this->checkExistCount($rows);
	}

	/**
	 * This will update the status of the user by key.
	 *
	 * @param string $key
	 * @param string $status
	 * @return bool
	 */
	public function updateStatusByKey(string $key, string $status): bool
	{
		return $this->table()
			->update("status = ?")
			->where("auth_keys = ?")
			->execute([$status, $key]);
	}
}