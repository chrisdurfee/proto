<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * FollowerUser
 *
 */
class FollowerUser extends Migration
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
		$this->create('follower_users', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->integer('follower_user_id', 30);

			// Indexes
			$table->index('user_id')->fields('user_id', 'follower_user_id');
			$table->index('follower_user_id')->fields('follower_user_id');

			// FKs
			$table->foreign('user_id')
				->references('id')
				->on('users')
				->onDelete('cascade');

		   $table->foreign('follower_user_id')
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
		  $this->drop('follower_users');
	}
}