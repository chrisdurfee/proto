<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Database\Migrations\Migration;

/**
 * Migration for the users table.
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
			// Primary key
			$table->id();

			// Identity & login
			$table->uuid();
			$table->varchar('username', 100);
			$table->varchar('password', 255);
			$table->varchar('email', 255)->nullable();
			$table->varchar('mobile', 14)->nullable();
			$table->tinyInteger('multi_factor_enabled')->default(0);
			$table->timestamp('last_password_change_at')->nullable();

			// Profile
			$table->varchar('first_name', 100)->nullable();
			$table->varchar('last_name', 100)->nullable();
			$table->varchar('display_name', 150)->nullable();
			$table->varchar('image', 255)->nullable();
			$table->varchar('cover_image_url', 255)->nullable();
			$table->text('bio')->nullable();
			$table->date('dob')->nullable();
			$table->enum('gender', 'male','female','other','prefer_not_say')->nullable();

			// Locale
			$table->varchar('street_1', 255)->nullable();
			$table->varchar('street_2', 255)->nullable();
			$table->varchar('city', 255)->nullable();
			$table->varchar('state', 100)->nullable();
			$table->varchar('postal_code', 20)->nullable();
			$table->varchar('timezone', 50)->nullable();
			$table->varchar('language', 10)->nullable();
			$table->varchar('currency', 3)->nullable();
			$table->varchar('country', 100)->nullable();

			// Status & flags
			$table->enum('status', 'active','inactive','pending')->default("'active'");
			$table->tinyInteger('enabled')->default(1);
			$table->timestamp('email_verified_at')->nullable();
			$table->tinyInteger('marketing_opt_in')->default(0);
			$table->timestamp('accepted_terms_at')->nullable();
			$table->tinyInteger('trial_mode')->default(0);
			$table->integer('trial_days_left', 3)->default(0);

			// Session & activity
			$table->timestamp('last_login_at')->nullable();

			// Audit & soft‐delete
			$table->createdAt();
			$table->integer('created_by', 30)->nullable();
			$table->updatedAt();
			$table->integer('updated_by', 30)->nullable();
			$table->deletedAt();

			// Additional fields
			$table->integer('follower_count', 30)->default(0);

			// Indexes for quick lookups
			$table->index('first_name')->fields('first_name', 'last_name', 'status');
			$table->index('last_name')->fields('last_name', 'first_name', 'status');
			$table->index('username')->fields('username', 'password', 'id');
			$table->index('email')->fields('email', 'password', 'id');
			$table->index('mobile')->fields('mobile');
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