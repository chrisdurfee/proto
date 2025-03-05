<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the proto_error_log table.
 *
 * @package Proto\Database\Migrations
 */
class ErrorLog extends Migration
{
	/**
	 * @var string $connection The database connection name.
	 */
	protected string $connection = 'default';

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$this->create('proto_error_log', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->updatedAt();
			$table->deletedAt()->nullable();
			$table->int('error_number', 20);
			$table->text('error_message');
			$table->varchar('error_file', 255);
			$table->int('error_line', 20);
			$table->text('error_trace')->nullable();
			$table->text('back_trace')->nullable();
			$table->varchar('env', 100);
			$table->varchar('url', 255)->nullable();
			$table->text('query')->nullable();
			$table->boolean('resolved')->setDefault(0);
			$table->varchar('error_ip', 45);

            // Indexes for faster queries on commonly searched columns
			$table->index('env')->fields('created_at', 'error_ip', 'env', 'resolved');
			$table->index('created_at')->fields('created_at');
            $table->index('error_number')->fields('error_number');
		});
	}

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('proto_error_log');
	}
}