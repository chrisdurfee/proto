<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * NotificationPreference
 *
 */
class NotificationPreference extends Migration
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
		$this->create('notification_preferences', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->tinyInteger('allow_email', 1)->default(1);
			$table->tinyInteger('allow_sms', 1)->default(1);
			$table->tinyInteger('allow_push', 1)->default(1);

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->unique('user_id')->fields('user_id');

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
		$this->drop('notification_preferences');
	}
}