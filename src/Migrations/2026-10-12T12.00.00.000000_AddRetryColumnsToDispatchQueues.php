<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * AddRetryColumnsToDispatchQueues
 *
 * Adds per-row retry tracking (`attempts`, `next_attempt_at`) to the
 * email, SMS and web push dispatch queues so a failed send is retried
 * with backoff instead of stranding its whole batch in `sending`.
 */
class AddRetryColumnsToDispatchQueues extends Migration
{
	/**
	 * @var string $connection
	 */
	protected string $connection = 'default';

	/**
	 * @var array $tables
	 */
	protected array $tables = [
		'email_queue',
		'sms_queue',
		'web_push_queue'
	];

	/**
	 * Runs the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		foreach ($this->tables as $tableName)
		{
			$this->alter($tableName, function($table)
			{
				$table->add('attempts')->int(10)->default(0)->after('priority');
				$table->add('next_attempt_at')->datetime()->nullable()->after('attempts');
			});
		}
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		foreach ($this->tables as $tableName)
		{
			$this->alter($tableName, function($table)
			{
				$table->drop('attempts');
				$table->drop('next_attempt_at');
			});
		}
	}
}
