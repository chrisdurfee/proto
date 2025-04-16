<?php declare(strict_types=1);

namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * LoginLog
 *
 * @package Modules\User\Models
 */
class LoginLog extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'login_log';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'l';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'direction',
		'ip'
	];
}