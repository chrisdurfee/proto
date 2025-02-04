<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Storage\WebPushUserStorage;

class WebPushUser extends Model
{
	protected static $tableName = 'web_push_users';

	protected static $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'endpoint',
		'authKeys',
		'status'
	];

	public function getByUser($userId, $limit = false)
	{
		return $this->storage->getByUser($userId, $limit);
	}

	public function getByClientId($clientId, $type = null)
	{
		return $this->storage->getByClientId($clientId, $type);
	}

	public function updateStatusByKey($key, $status)
	{
		return $this->storage->updateStatusByKey($key, $status);
	}

	protected static $storageType = WebPushUserStorage::class;
}