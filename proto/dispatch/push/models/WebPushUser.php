<?php declare(strict_types=1);
namespace Proto\Dispatch\Push\Models;

use Proto\Dispatch\Push\Storage\WebPushUserStorage;
use Proto\Models\Model;

/**
 * WebPushUser
 *
 * This class represents a web push user and provides methods to interact with the storage.
 *
 * @package Proto\Dispatch\Push\Models
 */
class WebPushUser extends Model
{
	/**
	 * The table name associated with the model.
	 *
	 * @var string
	 */
	protected static string $tableName = 'web_push_users';

	/**
	 * The fields associated with the model.
	 *
	 * @var array
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'endpoint',
		'authKeys',
		'status'
	];

	/**
	 * The storage type associated with the model.
	 *
	 * @var string
	 */
	protected static string $storageType = WebPushUserStorage::class;
}