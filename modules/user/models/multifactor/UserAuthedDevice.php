<?php declare(strict_types=1);
namespace Modules\User\Models\Multifactor;

use Proto\Models\Model;

/**
 * UserAuthedDevice
 *
 * This model represents the devices that a user has authenticated from.
 *
 * @package Modules\User\Models\Multifactor
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

}