<?php declare(strict_types=1);
namespace Proto\Database\Migrations\Models;

use Proto\Database\Migrations\Storage\MigrationStorage;
use Proto\Models\Model;

/**
 * Migration
 *
 * Handles database migrations.
 *
 * @package Proto\Database\Migrations\Models
 */
class Migration extends Model
{
	/**
	 * @var string $tableName The name of the database table.
	 */
	protected static $tableName = 'migrations';

	/**
	 * @var string $alias The alias of the database table.
	 */
	protected static $alias = 'm';

	/**
	 * @var array $fields The fields in the migrations table.
	 */
	protected static $fields = [
		'id',
		'createdAt',
		'migration',
		'groupId'
	];

	/**
	 * @var string $storageType The storage type for migrations.
	 */
	protected static $storageType = MigrationStorage::class;

}