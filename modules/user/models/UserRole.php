<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * UserRole
 *
 * This is the model class for the pivot table "user_roles".
 *
 * @package Modules\User\Models
 */
class UserRole extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_roles';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ur';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'userId',
		'roleId',
		'createdAt',
		'updatedAt'
	];

	/**
	 * Define joins for the Permission model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		/**
		 * This will create a bridge table join for the role_permissions table
		 * and the roles table.
		 */
		Role::many($builder)
			->on(['roleId', 'id'])
			->fields(
				'id',
				'name',
				'slug',
				'description'
			);
	}

	/**
	 * This will delete a user role by the userId and roleId.
	 *
	 * @param mixed $userId
	 * @param mixed $roleId
	 * @return bool
	 */
	public function deleteUserRole(mixed $userId, mixed $roleId): bool
	{
		return $this->storage
			->table()
			->delete()
			->where('user_id = ?', 'role_id = ?')
			->execute([$userId, $roleId]);
	}
}