<?php declare(strict_types=1);
namespace Modules\Auth\Models;

use Modules\Auth\Storage\SecureRequestStorage;
use Proto\Models\Model;

/**
 * SecureRequest
 *
 * @package Modules\Auth\Models
 */
class SecureRequest extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'secure_requests';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 's';

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
	protected static string $storageType = SecureRequestStorage::class;
}