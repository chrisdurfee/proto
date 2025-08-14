<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * JobTable
 *
 */
class JobTable extends Migration
{
	/**
	 * @var string $connection
	 */
	protected string $connection = 'default';

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		// Create jobs table
		$this->createJobsTable();

		// Create failed_jobs table
		$this->createFailedJobsTable();
	}

	/**
	 * Reverse the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('failed_jobs');
		$this->drop('jobs');
	}

	/**
	 * Create the jobs table.
	 *
	 * @return void
	 */
	protected function createJobsTable(): void
	{
		$this->create('jobs', function($table)
		{
			$table->varchar('id', 255)->primary();
			$table->varchar('queue', 100)->default('"default"');
			$table->varchar('job_class', 255);
			$table->varchar('job_name', 255);
			$table->longText('data')->nullable();
			$table->tinyInt('attempts')->default(0);
			$table->tinyInt('max_retries')->default(3);
			$table->int('timeout')->default(300);
			$table->enum('status', 'pending', 'processing', 'completed', 'failed')->default('"pending"');
			$table->createdAt();
			$table->datetime('available_at');
			$table->datetime('reserved_at')->nullable();
			$table->datetime('processed_at')->nullable();

			// Indexes for performance
			$table->index('queue')->fields('queue', 'status');
			$table->index('status')->fields('status', 'available_at');
			$table->index('available_at')->fields('available_at');
			$table->index('reserved_at')->fields('reserved_at');
			$table->index('processed_at')->fields('processed_at');
		});
	}

	/**
	 * Create the failed_jobs table.
	 *
	 * @return void
	 */
	protected function createFailedJobsTable(): void
	{
		$this->create('failed_jobs', function($table)
		{
			$table->varchar('id', 255)->primary();
			$table->varchar('job_id', 255);
			$table->varchar('queue', 100);
			$table->varchar('job_class', 255);
			$table->varchar('job_name', 255);
			$table->longText('data')->nullable();
			$table->tinyInt('attempts');
			$table->text('error');
			$table->datetime('failed_at');

			$table->index('job_id')->fields('job_id');
			$table->index('queue')->fields('queue');
			$table->index('job_class')->fields('job_class');
			$table->index('job_name')->fields('job_name');
			$table->index('failed_at')->fields('failed_at');
		});
	}
}