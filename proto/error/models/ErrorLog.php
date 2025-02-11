<?php declare(strict_types=1);
namespace Proto\Error\Models;

use Proto\Error\Storage\ErrorLogStorage;
use Proto\Models\Model;

/**
 * ErrorLog
 *
 * This is the model class for table "proto_error_log".
 *
 * @package Proto\Models
 */
class ErrorLog extends Model
{
	/**
	 * @var string $tableName
	 */
	protected static $tableName = 'proto_error_log';

	/**
	 * @var string $alias
	 */
    protected static $alias = 'e';

	/**
	 * @var array $fields
	 */
	protected static $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'deletedAt',
		'errorNumber',
        'errorMessage',
        'errorFile',
        'errorLine',
        'errorTrace',
        'backTrace',
        'env',
		'url',
		'query',
		'resolved',
        'errorIp'
	];

	/**
	 * This will update the model resolved status.
	 *
	 * @return bool
	 */
	public function updateResolved(): bool
	{
		return $this->storage->updateResolved();
	}

	/**
	 * @var string $storageType
	 */
	protected static $storageType = ErrorLogStorage::class;
}