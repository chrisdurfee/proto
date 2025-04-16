<?php declare(strict_types=1);
namespace Modules\User\Models;

use Modules\User\Storage\LoginAttemptStorage;
use Proto\Models\Model;

/**
 * LoginAttempt
 *
 * @package Modules\User\Models
 */
class LoginAttempt extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'login_attempts';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'a';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'ipAddress',
		'usernameId'
	];

	/**
	 * Define joins for the User model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		$builder->left('login_attempt_usernames', 'lu')
			->on(['usernameId', 'id'])
			->fields('username');
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = LoginAttemptStorage::class;
}