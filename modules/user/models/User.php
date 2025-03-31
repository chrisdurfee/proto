<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

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
		static::bridge(UserRole::class)
			->many(Role::class)
			->on(['roleId', 'id'])
			->fields(
				'id',
				'name',
				'slug',
				'description'
			);
	}
}