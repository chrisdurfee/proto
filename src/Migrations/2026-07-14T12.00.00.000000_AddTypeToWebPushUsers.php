<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * AddTypeToWebPushUsers
 *
 * Adds a `type` discriminator to push subscriptions so native-shell
 * APNs device tokens can live alongside VAPID web-push endpoints.
 */
class AddTypeToWebPushUsers extends Migration
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
		$this->alter('web_push_users', function($table)
		{
			$table->add('type')->enum('webpush', 'apns')->default("'webpush'")->after('endpoint');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->alter('web_push_users', function($table)
		{
			$table->drop('type');
		});
	}
}
