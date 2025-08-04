<?php declare(strict_types=1);
namespace Proto\Dispatch\Push\Storage;

use Proto\Storage\Storage;

/**
 * WebPushUserStorage
 *
 * This class provides methods to interact with the web push user storage.
 *
 * @package Proto\Storage
 */
class WebPushUserStorage extends Storage
{
	/**
	 * Retrieves web push users by user ID.
	 *
	 * @param int $userId The user ID.
	 * @param bool $limit The limit of records to retrieve.
	 * @return array|false The retrieved records or false if not found.
	 */
	public function getByUser(int $userId, bool $limit = false): array|false
	{
		if (!isset($userId))
		{
			return false;
		}

		$sql = $this->select()
			->where('user_id = ?');

		if ($limit)
		{
			$sql->limit(1);
		}

		return $sql->fetch([$userId]);
	}

	/**
	 * Retrieves web push users by client ID.
	 *
	 * @param string $clientId The client ID.
	 * @param string|null $type The type of client.
	 * @return array The retrieved records.
	 */
	public function getByClientId(string $clientId, ?string $type = null): array
	{
		if (!isset($clientId))
		{
			return [];
		}

		$select = $this->table('w')->select('*');

		if ($type)
		{
			$select
				->join(function($join)
				{
					$join->left('user_locations', 'l')->on(['w.user_id', 'l.user_id']);
					$join->left('users', 'u')->on(['l.user_id', 'u.id']);
				})
				->where('l.client_id = ?', "u.allow_access = '1'", "u.is_guest != '1'", "(n.name = ? AND ns.status != '0' OR ns.status IS NULL)");

			return $this->fetch($select, [$clientId, $type]);
		}

		$select
			->join(function($join)
			{
				$join->left('user_locations', 'l')->on(['w.user_id', 'l.user_id']);
				$join->left('users', 'u')->on(['l.user_id', 'u.id']);
				$join->left('user_notification_settings', 's')->on(['u.id', 's.user_id']);
				$join->left('notification_types', 'n')->on(['s.notification_id', 'n.id']);
			})
			->where('l.client_id = ?', "u.allow_access = '1'", "u.is_guest != '1'", "w.status = 'active'");

		return $this->fetch($select, [$clientId]);
	}

	/**
	 * Updates the status of a web push user by key.
	 *
	 * @param string $key The key to identify the user.
	 * @param string $status The new status.
	 * @return bool The result of the update operation.
	 */
	public function updateStatusByKey(string $key, string $status): bool
	{
		return $this->table()
			->update('status = ?')
			->where('auth_keys = ?')
			->execute([$status, $key]);
	}

	/**
	 * Checks if a web push user exists based on the provided data.
	 *
	 * @param object $data The data to check.
	 * @return bool True if the user exists, false otherwise.
	 */
	protected function exists(object $data): bool
	{
		$sql = $this->select('id')
			->where('user_id = ?', 'endpoint = ?')
			->limit(1);

		$rows = $this->fetch($sql, [$data->userId, $data->endpoint]);
		return $this->checkExistCount($rows);
	}
}