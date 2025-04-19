<?php declare(strict_types=1);
namespace Modules\User\Storage\Multifactor;

use Proto\Storage\Storage;

/**
 * UserAuthedDeviceStorage
 *
 * This will handle the storage for the user authenticated devices.
 *
 * @package Modules\User\Storage\Multifactor
 */
class UserAuthedDeviceStorage extends Storage
{
	/**
	 * This will verify if the device exists for the user.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists($data): bool
	{
		$rows = $this->select('id')
			->where("{$this->alias}.user_id = ?", "{$this->alias}.guid = ?")
			->limit(1)
			->fetch([$data->user_id, $data->guid]);

		return $this->checkExistCount($rows);
	}

	/**
	 * This will update the accessed at time.
	 *
	 * @param string $userId
	 * @param string $guid
	 * @return bool
	 */
	public function updateAccessedAt(string $userId, string $guid): bool
	{
		$dateTime = date('Y-m-d H:i:s');

		return $this->table()
			->update("accessed_at = '{$dateTime}'")
			->where("user_id = ?", "guid = ?")
			->execute([$userId, $guid]);
	}
}