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
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'proto_error_log';

	/**
	 * @var string|null $alias
	 */
    protected static ?string $alias = 'e';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
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
	 * @var string $storageType
	 */
	protected static string $storageType = ErrorLogStorage::class;
}