<?php declare(strict_types=1);

namespace Modules\User\Models\Mutifactor;

use Proto\Models\Model;

/**
 * UserAuthedLocation
 * 
 * @package Modules\User\Models\Mutifactor
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
		' createdAt',
		' updatedAt',
		' city',
		' region',
		' regionCode',
		' country',
		' countryCode',
		' postal',
		' position',
		' timezone',
		' deletedAt'
	];

}