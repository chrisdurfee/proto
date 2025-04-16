<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the login_attempt_usernames table.
 */
class LoginAttemptUsername extends Migration
{
	/**
	 * @var string $connection
	 */
	protected string $connection = 'default';

	/**
	 * Runs the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$this->create('login_attempt_usernames', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->varchar('username', 255);

			// Indexes
			$table->index('username')->fields('username')->unique();
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
		$this->drop('login_attempt_usernames');
	}
}
