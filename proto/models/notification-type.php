<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Storage\NotificationTypeStorage;

class NotificationType extends Model
{
	protected static $tableName = 'notification_types';

	protected static $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'name'
	];

	public function getByName($name)
	{
		return $this->storage->getByName($name);
	}

	protected static $storageType = NotificationTypeStorage::class;
}