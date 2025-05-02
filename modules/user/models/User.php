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
		 * This will create a bridge table join for the user_roles table
		 * and the roles table.
		 */
		UserRole::bridge($builder)
			->many(Role::class)
			->on(['roleId', 'id'])
			->fields(
				'id',
				'name',
				'slug'
			)

			/**
			 * This will create a bridge table join from the role to role_permissions table
			 * and the role_permissions to the permissions table.
			 */
			->bridge(RolePermission::class)
				->on(['id', 'roleId'])
				->many(Permission::class)
				->on(['permissionId', 'id'])
				->fields(
					'id',
					'name',
					'slug'
				);

		NotificationPreference::one($builder)
			->on(['id', 'userId'])
			->fields(
				'allowEmail',
				'allowSms',
				'allowPush'
			);
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserStorage::class;
}