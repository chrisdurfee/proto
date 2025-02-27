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
}