<?php declare(strict_types=1);
namespace Modules\Auth\Models;

use Modules\Auth\Storage\PasswordRequestStorage;
use Proto\Models\Model;

/**
 * PasswordRequest
 *
 * This will set up the password request model.
 *
 * @package Modules\Auth\Models
 */
class PasswordRequest extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'password_requests';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'pr';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'requestId',
		'status'
	];

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = PasswordRequestStorage::class;
}