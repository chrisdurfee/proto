<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * Role
 *
 * This is the model class for table "roles".
 *
 * @package Modules\User\Models
 */
class Role extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'roles';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'r';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'name',
		'slug',
		'description',
		'createdAt',
		'updatedAt'
	];

	/**
	 * Define joins for the Role model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		/**
		 * This will create a bridge table join for the role_permissions table
		 * and the permissions table.
		 */
		RolePermission::bridge($builder)
			->many(Permission::class)
			->on(['permissionId', 'id'])
			->fields(
				'id',
				'name',
				'slug',
				'description',
				'module'
			);
	}
}