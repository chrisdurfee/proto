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
		RoleUser::bridge($builder)
			->fields(
				'organizationId'
			)
			->many(Role::class)
			->on(['roleId', 'id'])
			->fields(
				'id',
				'name',
				'slug'
			)

			/**
			 * This will create a bridge table join from the role to permission_roles table
			 * and the permission_roles to the permissions table.
			 */
			->bridge(PermissionRole::class)
				->on(['id', 'roleId'])
				->many(Permission::class)
				->on(['permissionId', 'id'])
				->fields(
					'id',
					'name',
					'slug'
				);

		OrganizationUser::bridge($builder)
			->many(Organization::class)
			->on(['organizationId', 'id'])
			->fields(
				'id',
				'name'
			);

		NotificationPreference::one($builder)
			->fields(
				[['IF(allow_email = 0, 0, 1)'], 'allowEmail'],
				[['IF(allow_sms = 0, 0, 1)'], 'allowSms'],
				[['IF(allow_push = 0, 0, 1)'], 'allowPush']
			);
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserStorage::class;
}