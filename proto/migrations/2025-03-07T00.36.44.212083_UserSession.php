<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the user_sessions table.
 */
class UserSession extends Migration
{
	/**
	 * @var string $connection The database connection name.
	 */
	protected string $connection = 'default';

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$this->create('user_sessions', function($table)
		{
			$table->id();
			$table->varchar('access', 255);
			$table->text('data')->nullable();
			$table->createdAt();
			$table->updatedAt();
		});
	}

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('user_sessions');
	}
}