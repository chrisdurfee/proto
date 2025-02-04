<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Storage\UserLocationStorage;

class UserLocation extends Model
{
	protected static $tableName = 'user_locations';

	protected static $fields = [
		'id',
		'userId',
		'clientId'
	];

	public function getLocations($userId)
	{
		return $this->storage->getLocations($userId);
	}

	public function getUsers($clientId, $filter = null)
	{
		return $this->storage->getUsers($clientId, $filter);
	}

	public function deleteMissingLocations($userId, $clientIds)
	{
		return $this->storage->deleteMissingLocations($userId, $clientIds);
	}

	public function deleteByUserId($userId)
	{
		return $this->storage->deleteByUserId($userId);
	}

	protected static $storageType = UserLocationStorage::class;
}