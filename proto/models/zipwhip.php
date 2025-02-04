<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * Zipwhip
 *
 * This is the zipwhip model class. This will be used to send text messages.
 *
 * @package Proto\Models
 */
class Zipwhip extends Model
{
	/**
	 * @var string $tableName
	 */
	protected static $tableName = 'zipwhip';

	/**
	 * @var array $fields
	 */
	protected static $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'clientId',
		'number',
		'session',
		'status'
	];
}