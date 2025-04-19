<?php declare(strict_types=1);
namespace Modules\User\Models\Multifactor;

use Proto\Models\Model;
use Modules\User\Storage\Multifactor\UserAuthedConnectionStorage;

/**
 * UserAuthedConnection
 *
 * This model represents the connections that a user has authenticated from.
 *
 * @package Modules\User\Models\Multifactor
 */
class UserAuthedConnection extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_authed_connections';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ac';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'accessedAt',
		'ipAddress',
		'deviceId',
		'locationId',
		'deletedAt'
	];

	/**
	 * Define joins for the model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		UserAuthedDevice::one($builder)
			->on(['deviceId', 'id']);

		UserAuthedLocation::one($builder)
			->on(['locationId', 'id'])
			->fields(
				'city',
				'region',
				'regionCode',
				'country',
				'countryCode'
			);
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserAuthedConnectionStorage::class;
}