<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the roles table.
 *
 * @package Modules\User\Migrations
 */
class Role extends Migration
{
	/**
	 * @var string $connection The database connection name.
	 */
	protected string $connection = 'default';

	/**
	 * Runs the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$this->create('roles', function($table)
		{
			$table->id();
			$table->varchar('name', 100);
			$table->varchar('slug', 100);
			$table->text('description')->nullable();
			$table->createdAt();
			$table->updatedAt();

			// Indexes
			$table->index('name')->fields('name');
			$table->index('slug')->fields('slug');
			$table->index('created_at')->fields('created_at');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('roles');
	}
}