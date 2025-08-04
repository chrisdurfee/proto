<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * RoleUser
 *
 * This is the model class for the pivot table "role_users".
 *
 * @package Modules\User\Models
 */
class RoleUser extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'role_users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ru';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'userId',
		'roleId',
		'organizationId',
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
		$builder
			->one(Role::class, fields: ['id', 'name', 'slug']);
	}

	/**
	 * This will delete a user role by the userId and roleId.
	 *
	 * @param mixed $userId
	 * @param mixed $roleId
	 * @param int|null $organizationId
	 * @return bool
	 */
	public function deleteUserRole(mixed $userId, mixed $roleId, ?int $organizationId = null): bool
	{
		$params = [$userId, $roleId];
		$where = [
			'user_id = ?',
			'role_id = ?'
		];

		if ($organizationId !== null)
		{
			$where[] = 'organization_id = ?';
			$params[] = $organizationId;
		}

		return $this->storage
			->table()
			->delete()
			->where(...$where)
			->execute($params);
	}
}