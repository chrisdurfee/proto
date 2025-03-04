<?php declare(strict_types=1);
namespace Proto\Database\Migrations\Storage;

use Proto\Storage\Storage;

/**
 * MigrationStorage
 *
 * Handles storage operations for migrations.
 *
 * @package Proto\Database\Migrations\Storage
 */
class MigrationStorage extends Storage
{
	/**
	 * This will return the last migration.
	 *
	 * @return array
	 */
	public function getLastMigration(): array
	{
		return $this->select()
			->join(function($join)
			{
				$join->right(['
					SELECT
						group_id
					FROM
						{$this->tableName}
					ORDER BY created_at DESC
					LIMIT 1
				'], 't')->on('m.group_id = t.group_id');
			})
			->fetch();
	}
}