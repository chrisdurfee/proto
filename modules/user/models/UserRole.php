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
}