<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * EmailVerification
 *
 */
class EmailVerification extends Migration
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
		$this->create('email_verification', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->int('user_id', 30);
			$table->varchar('request_id', 255);
			$table->enum('status', 'pending', 'complete')->default('"pending"');

			// Indexes
			$table->index('user_id')->fields('user_id', 'status');
			$table->index('request_id')->fields('request_id', 'user_id', 'created_at', 'status');
			$table->index('created_at')->fields('created_at');

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
		$this->drop('email_verification');
	}
}