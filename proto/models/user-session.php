<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * UserSession
 *
 * This will create a user session model.
 *
 * @package Proto\Models
 */
class UserSession extends Model
{
	/**
	 * @var string $tableName
	 */
	protected static $tableName = 'user_sessions';

	/**
	 * @var array $fields
	 */
	protected static $fields = [
		'id',
		'access',
		'data'
	];
}