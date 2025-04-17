<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the user_authed_devices table.
 *
 * @package App\Migrations
 */
class UserAuthedDevice extends Migration
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
		$this->create('user_authed_devices', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->int('user_id', 30);
			$table->datetime('accessed_at')->nullable();
			$table->varchar('guid', 255);
			$table->varchar('platform', 50)->nullable();
			$table->varchar('brand', 100)->nullable();
			$table->varchar('vendor', 100)->nullable();
			$table->varchar('version', 50)->nullable();
			$table->boolean('touch')->default(0);
			$table->boolean('mobile')->default(0);
			$table->deletedAt();

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->index('access_user')->fields('user_id', 'accessed_at');

			// Foreign keys
			$table->foreign('user_id')
				->references('id')
				->on('users')
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
		$this->drop('user_authed_devices');
	}
}