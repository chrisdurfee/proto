<?php declare(strict_types=1);
namespace Modules\Auth\Models\Multifactor;

use Proto\Models\Model;
use Modules\Auth\Storage\Multifactor\UserAuthedDeviceStorage;

/**
 * UserAuthedDevice
 *
 * This model represents the devices that a user has authenticated from.
 *
 * @package Modules\Auth\Models\Multifactor
 */
class UserAuthedDevice extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_authed_devices';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ad';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'accessedAt',
		'guid',
		'platform',
		'brand',
		'vendor',
		'version',
		'touch',
		'mobile',
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
		UserAuthedConnection::many($builder)
            ->on(['id', 'deviceId'])
            ->fields('ipAddress')

			/**
			 * This will define the relationship between the authenticated connection
			 * and its location.
			 */
            ->one(UserAuthedLocation::class)
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
	protected static string $storageType = UserAuthedDeviceStorage::class;
}