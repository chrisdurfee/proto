<?php declare(strict_types=1);
namespace Proto\Http\Session\Models;

use Proto\Models\Model;

/**
 * UserSession
 *
 * This will create a user session model.
 *
 * @package Proto\Http\Session\Models
 */
class UserSession extends Model
{
	/**
	 * @var string $tableName
	 */
	protected static string $tableName = 'user_sessions';

	/**
	 * @var string $alias
	 */
	protected static string $alias = 'us';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'access',
		'data'
	];
}