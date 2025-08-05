<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the user_sessions table.
 *
 * @suppresswarnings PHP6609
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
			$table->varchar('id', 256);
			$table->varchar('access', 256)->nullable();
			$table->text('data')->nullable();
			$table->timestamps();

			$table->index('id')->fields('id');
			$table->index('access')->fields('access');
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