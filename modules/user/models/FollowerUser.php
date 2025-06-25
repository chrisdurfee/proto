<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * FollowerUser
 *
 * This is the model class for the pivot table "follower_users".
 *
 * @package Modules\User\Models
 */
class FollowerUser extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'follower_users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'fu';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'followerUserId'
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
			->on(['followerUserId', 'id']);
	}
}