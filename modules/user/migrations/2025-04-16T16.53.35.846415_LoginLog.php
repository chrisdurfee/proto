<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the login_log table.
 *
 * @package App\Migrations
 */
class LoginLog extends Migration
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
		$this->create('login_log', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->int('user_id', 30);
			$table->enum('direction', 'login', 'logout');
			$table->varchar('ip', 45);

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->index('direction')->fields('direction');
			$table->index('created_at')->fields('user_id', 'created_at');

			// Foreign keys
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('login_log');
	}
}
