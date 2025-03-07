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
			$table->varchar('firstName', 100);
			$table->varchar('lastName', 100);
			$table->varchar('role', 50);
			$table->int('status', 11);
			$table->timestamp('emailVerifiedAt')->nullable();
			$table->createdAt();
			$table->updatedAt();
			$table->deletedAt()->nullable();

			// Indexes for commonly searched columns
			$table->index('username')->fields('username');
			$table->index('email')->fields('email');
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
