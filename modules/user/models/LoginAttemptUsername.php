<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;
use Modules\User\Storage\LoginAttemptUsernameStorage;

/**
 * LoginAttemptUsername
 *
 * This will handle the login attempt usernames.
 *
 * @package Modules\User\Models
 */
class LoginAttemptUsername extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'login_attempt_usernames';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'lu';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'username'
	];

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = LoginAttemptUsernameStorage::class;
}