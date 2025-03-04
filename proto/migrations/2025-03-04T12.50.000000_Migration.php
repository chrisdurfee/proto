<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the migrations table.
 *
 * @package Proto\Database\Migrations
 */
class CreateMigrationsTable extends Migration
{
	/**
	 * @var string $connection The database connection name.
	 */
	protected $connection = 'default';

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$this->create('migrations', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->varchar('migration', 255);
			$table->int('group_id', 30);

			$table->index('groupId')->fields('group_id', 'created_at');
			$table->index('created')->fields('created_at');
		});
	}

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('migrations');
	}
}
