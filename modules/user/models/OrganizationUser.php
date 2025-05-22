<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * OrganizationUser
 *
 * This is the model class for the pivot table "organization_user".
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

	/**
	 * Define joins for the organization user model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		Organization::one($builder)
			->on(['organizationId', 'id'])
			->fields(
				['name', 'organizationName']
			);
	}
}