<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;
use Modules\User\Storage\UserStorage;

/**
 * User
 *
 * This is the model class for table "users".
 *
 * @package Modules\User\Models
 */
class User extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'u';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'username',
		'email',
		'password',
		'firstName',
		'lastName',
		'image',
		'status',
		'emailVerifiedAt',
		'enabled',
		'createdAt',
		'updatedAt',
		'deletedAt'
	];

	/**
	 * Fields to exclude when exporting.
	 *
	 * @var array
	 */
	protected static array $fieldsBlacklist = [
		'password'
	];

	/**
	 * Define joins for the User model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		/**
		 * This will join the user roles and permissions.
		 */
		$builder
			->belongsToMany(Role::class, pivotFields: ['organizationId'])
			->belongsToMany(Permission::class);

		$builder
			->belongsToMany(Organization::class, ['id', 'name']);

		$builder
			->one(NotificationPreference::class, fields: [
				[['IF(allow_email = 0, 0, 1)'], 'allowEmail'],
				[['IF(allow_sms = 0, 0, 1)'], 'allowSms'],
				[['IF(allow_push = 0, 0, 1)'], 'allowPush']
			]);
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserStorage::class;
}