<?php declare(strict_types=1);
namespace Proto\Dispatch\Email\Unsubscribe\Models;

use Proto\Dispatch\Email\Unsubscribe\Storage\UnsubscribeStorage;
use Proto\Models\Model;

/**
 * Unsubscribe
 *
 * @package Proto\Dispatch\Email\Unsubscribe\Models
 */
class Unsubscribe extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'unsubscribe';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'u';

    /**
	 * Identifier key name.
	 *
	 * @var string
	 */
	protected static string $idKeyName = 'email';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'createdAt',
		'updatedAt',
		'email',
		'requestId'
	];

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UnsubscribeStorage::class;
}