<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * WebPush
 *
 * Migration for the web_push_users table.
 */
class WebPush extends Migration
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
		$this->create('web_push_users', function ($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->text('endpoint');
			$table->json('auth_keys');
			$table->enum('status', 'active', 'inactive')->default('"active"');

			// Indexes
			$table->index('user_id')->fields('user_id', 'status');
			$table->index('endpoint')->fields('endpoint');
			$table->index('created_at')->fields('created_at');
			$table->index('auth_keys')->fields('auth_keys');

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
		$this->drop('web_push_users');
	}
}
