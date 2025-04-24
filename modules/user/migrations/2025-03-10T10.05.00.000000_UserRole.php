<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the user_roles pivot table.
 */
class UserRole extends Migration
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
		$this->create('user_roles', function($table)
		{
			$table->id();
			$table->int('user_id', 20);
			$table->int('role_id', 20);
			$table->createdAt();
			$table->updatedAt();

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->index('role_id')->fields('role_id');
			$table->index('user_role')->fields('user_id', 'role_id')->unique();

			// Foreign keys
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('user_roles');
	}
}