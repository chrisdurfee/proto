<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Storage\WebPushUserStorage;

class UserNotificationSetting extends Model
{
	protected static $tableName = 'user_notification_settings';

	protected static $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'notificationId',
		'userId',
		'status'
	];

	public function getByUserId($userId)
	{
		return $this->storage->getByUserId($userId);
	}

	protected static $storageType = WebPushUserStorage::class;
}