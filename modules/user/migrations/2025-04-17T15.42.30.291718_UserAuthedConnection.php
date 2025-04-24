<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the user_authed_connections table.
 */
class UserAuthedConnection extends Migration
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
		$this->create('user_authed_connections', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->dateTime('accessed_at')->nullable();
			$table->varchar('ip_address', 45);
			$table->int('device_id', 30)->nullable();
			$table->int('location_id', 30)->nullable();
			$table->deletedAt();

			// Indexes
			$table->index('ip_address')->fields('ip_address');
			$table->index('device_id')->fields('device_id');
			$table->index('location_id')->fields('location_id');
			$table->index('device_time')->fields('device_id', 'accessed_at');

			// Foreign keys
			$table->foreign('device_id')
				->references('id')
				->on('user_authed_devices')
				->onDelete('cascade');

			$table->foreign('location_id')
				->references('id')
				->on('user_authed_locations')
				->onDelete('set null');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('user_authed_connections');
	}
}
