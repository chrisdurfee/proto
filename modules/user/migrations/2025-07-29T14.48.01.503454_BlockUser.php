<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * BlockUser
 *
 */
class BlockUser extends Migration
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
		$this->create('block_users', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->integer('block_user_id', 30);

			// Indexes
			$table->index('user_id')->fields('user_id', 'block_user_id');
			$table->index('block_user_id')->fields('block_user_id');

			// FKs
			$table->foreign('user_id')
				->references('id')
				->on('users')
				->onDelete('cascade');

		   $table->foreign('block_user_id')
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
		$this->drop('block_users');
	}
}