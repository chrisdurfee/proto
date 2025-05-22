<?php declare(strict_types=1);

namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * OrganizationUser
 * 
 * @package Modules\User\Models
 */
class OrganizationUser extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'organization_user';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'ou';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'organizationId'
	];

}