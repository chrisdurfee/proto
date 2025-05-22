<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * Organization
 *
 * This is the model class for the table "organizations".
 *
 * @package Modules\User\Models
 */
class Organization extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'organizations';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'o';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'name'
	];
}