<?php declare(strict_types=1);
namespace Modules\User\Models;

use Modules\User\Storage\UnsubscribeStorage;
use Proto\Models\Model;

/**
 * Unsubscribe
 *
 * @package Modules\Auth\Models
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