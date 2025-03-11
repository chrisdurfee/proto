<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * User
 *
 * This is the model class for table "users".
 *
 * @package Proto\Models
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
		'role',
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
		$join = UserRole::many($builder);

		// Role::many($join)
		// 	->fields([
		// 		['id', 'roleId'],
		// 		['name', 'roleName'],
		// 		['slug', 'roleSlug'],
		// 		['description', 'roleDescription'],
		// 		'permissions'
		// 	]);
	}
}