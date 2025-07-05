<?php declare(strict_types=1);
namespace Modules\Auth\Storage\Multifactor;

use Proto\Storage\Storage;

/**
 * UserAuthedConnectionStorage
 *
 * This will handle the storage for the user authenticated connections.
 *
 * @package Modules\Auth\Storage\Multifactor
 */
class UserAuthedConnectionStorage extends Storage
{
	/**
	 * This will verify if the device exists for the user.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists(object $data): bool
	{
		$rows = $this->select('id')
			->where("{$this->alias}.device_id = ?", "{$this->alias}.ip_address = ?")
			->limit(1)
			->fetch([$data->device_id, $data->ip_address]);

		return $this->checkExistCount($rows);
	}

	/**
	 * This will verify if the user is authenticated from the device.
	 *
	 * @param mixed $userId
	 * @param string $guid
	 * @param string $ipAddress
	 * @return bool
	 */
	public function isAuthed(
		mixed $userId,
		string $guid,
		string $ipAddress
	): bool
	{
		$row = $this->select('id')
			->where("{$this->alias}.ip_address = ?", "{$this->alias}.deleted_at IS NULL", "ud.user_id = ?", "ud.guid = ?")
			->limit(1)
			->first([$ipAddress, $userId, $guid]);

		return ($row !== null);
	}

	/**
	 * This will update the accessed at time.
	 *
	 * @param mixed $userId
	 * @param string $guid
	 * @param string $ipAddress
	 * @return bool
	 */
	public function updateAccessedAt(mixed $userId, string $guid, string $ipAddress): bool
	{
        $dateTime = date('Y-m-d H:i:s');

		return $this->table()
			->update("{$this->alias}.accessed_at = '{$dateTime}'")
            ->join(function($joins)
            {
                $joins->left('user_authed_devices', 'ud')
                    ->on("{$this->alias}.device_id = ud.id");
            })
			->where("{$this->alias}.ip_address = ?", "ud.user_id = ?", "ud.guid = ?")
			->execute([$ipAddress, $userId, $guid]);
	}
}