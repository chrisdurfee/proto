<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * BlockUser
 *
 * This is the model class for the pivot table "block_users".
 *
 * @package Modules\User\Models
 */
class BlockUser extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'block_users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'bu';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'blockUserId'
	];

	/**
	 * Define joins for the model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		$builder
			->one(
				User::class,
				fields: ['id', 'displayName', 'image']
			)
			->on(['blockUserId', 'id']);
	}

	/**
	 * Check if a block relationship exists.
	 *
	 * @param mixed $userId
	 * @param mixed $blockUserId
	 * @return bool
	 */
	public static function isAdded(mixed $userId, mixed $blockUserId): bool
	{
		$row = static::getBy([
			['user_id', $userId],
			['block_user_id', $blockUserId]
		]);
		return $row !== null;
	}
}