<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * UserAddress
 *
 * @package Modules\User\Models
 */
class UserAddress extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_address';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ua';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'street_1',
		'street_2',
		'city',
		'state',
		'postalCode',
		'country',
		'isPrimary'
	];

}