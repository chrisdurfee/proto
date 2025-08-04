<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Unsubscribe
 *
 */
class Unsubscribe extends Migration
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
		$this->create('unsubscribe', function($table)
		{
			$table->timestamps();
			$table->varchar('email', 255)->primary();
			$table->varchar('request_id', 255);

			// Indexes
			$table->index('email')->fields('email');
			$table->index('request_id')->fields('request_id', 'email', 'created_at');
			$table->index('created_at')->fields('created_at');

			// Foreign keys
			$table->foreign('email')
				->references('email')
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
		$this->drop('unsubscribe');
	}
}