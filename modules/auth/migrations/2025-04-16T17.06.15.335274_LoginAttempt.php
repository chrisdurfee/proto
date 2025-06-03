<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the login_attempts table.
 */
class LoginAttempt extends Migration
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
		$this->create('login_attempts', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->varchar('ip_address', 45);
			$table->int('username_id', 20);

			// Indexes
			$table->index('ip_address')->fields('ip_address');
			$table->index('username_id')->fields('username_id');
			$table->index('user_time')->fields('username_id', 'created_at');

			// Foreign keys
			$table->foreign('username_id')
				->references('id')
				->on('login_attempt_usernames')
				->onDelete('cascade');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('login_attempts');
	}
}