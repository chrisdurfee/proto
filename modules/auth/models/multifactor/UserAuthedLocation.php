<?php declare(strict_types=1);
namespace Modules\Auth\Models\Multifactor;

use Proto\Models\Model;
use Modules\Auth\Storage\Multifactor\UserAuthedLocationStorage;

/**
 * UserAuthedLocation
 *
 * This model represents the locations that a user has authenticated from.
 *
 * @package Modules\Auth\Models\Multifactor
 */
class UserAuthedLocation extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_authed_locations';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'al';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'ip',
		'network',
		'city',
		'region',
		'regionCode',
		'country',
		'countryCode',
		'postal',
		'continentCode',
		'countryCallingCode',
		'position',
		[['X(`position`)'], 'latitude'],
		[['Y(`position`)'], 'longitude'],
		'timezone',
		'deletedAt'
	];

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserAuthedLocationStorage::class;
}