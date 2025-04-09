<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the users table.
 *
 * @package Modules\User\Migrations
 */
class User extends Migration
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
		$this->create('users', function($table)
		{
			$table->id();
			$table->varchar('username', 100);
			$table->varchar('email', 255);
			$table->varchar('password', 255);
			$table->varchar('first_name', 100);
			$table->varchar('last_name', 100);
			$table->varchar('image', 255);
			$table->enum('status', 'online', 'offline', 'busy', 'away')->default("'offline'");
			$table->timestamp('email_verified_at')->nullable();
			$table->tinyInteger('enabled')->default(1);
			$table->createdAt();
			$table->updatedAt();
			$table->deletedAt();

			// Indexes for commonly searched columns
			$table->index('username')->fields('username', 'password');
			$table->index('email')->fields('email', 'password');
			$table->index('first_name')->fields('first_name', 'last_name');
			$table->index('last_name')->fields('last_name', 'first_name');
			$table->index('status')->fields('status');
			$table->index('created_at')->fields('created_at');
			$table->index('updated_at')->fields('updated_at');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('users');
	}
}
