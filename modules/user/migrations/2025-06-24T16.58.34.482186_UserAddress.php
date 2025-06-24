<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * UserAddress
 *
 */
class UserAddress extends Migration
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
		$this->create('user_address', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->integer('user_id', 30);
			$table->varchar('street_1', 255)->nullable();
			$table->varchar('street_2', 255)->nullable();
			$table->varchar('city', 100)->nullable();
			$table->varchar('state', 100)->nullable();
			$table->varchar('postal_code', 20)->nullable();
			$table->varchar('country', 100)->nullable();
			$table->boolean('is_primary')->default(false);

			// Indexes
			$table->index('user_id')->fields('user_id');
			$table->unique('user_primary_unique')->fields('user_id','is_primary');

			// FKs
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
		$this->drop('user_address');
	}
}