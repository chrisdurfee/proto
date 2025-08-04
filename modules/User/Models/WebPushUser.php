<?php declare(strict_types=1);
namespace Modules\User\Models;

use Modules\User\Storage\WebPushUserStorage;
use Proto\Models\Model;

/**
 * WebPushUser
 *
 * Handles the web push user model.
 *
 * @package Modules\User\Models
 */
class WebPushUser extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'web_push_users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'wp';

	/**
	 * @var array $fields
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
	 * This will decode the authKeys field.
	 *
	 * @param mixed $data
	 * @return object|null
	 */
	protected static function format(?object $data): ?object
	{
		if (isset($data->authKeys))
		{
			$data->authKeys = json_decode($data->authKeys);
		}

		return $data;
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = WebPushUserStorage::class;
}