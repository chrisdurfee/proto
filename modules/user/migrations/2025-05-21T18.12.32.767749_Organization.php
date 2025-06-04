<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Organization
 *
 * Adds `organizations` and the pivot `organization_user`.
 */
class Organization extends Migration
{
	/**
	 * @var string $connection
	 */
	protected string $connection = 'default';

	/**
	 * Runs the migration.
	 */
	public function up(): void
	{
		// ── Create organizations table
		$this->create('organizations', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->varchar('name', 255);

			// Unique on name
			$table->unique('name')->fields('name');
		});

		// ── Create pivot table for users ↔ organizations
		$this->create('organization_users', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->integer('organization_id', 30);

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->index('organization_id')->fields('organization_id');

			// FKs
			$table->foreign('user_id')
				  ->references('id')
				  ->on('users')
				  ->onDelete('cascade');

			$table->foreign('organization_id')
				  ->references('id')
				  ->on('organizations')
				  ->onDelete('cascade');
		});
	}

	/**
	 * Reverts the migration.
	 */
	public function down(): void
	{
		$this->drop('organization_users');
		$this->drop('organizations');
	}
}