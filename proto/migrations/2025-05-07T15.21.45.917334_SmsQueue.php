<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * SmsQueue
 *
 * Creates the sms_queue table for dispatching SMS messages.
 */
class SmsQueue extends Migration
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
		$this->create('sms_queue', function($table)
		{
			$table->id();
			$table->timestamps();
			$table->varchar('agent_id', 255);
			$table->varchar('dispatch_id', 255);
			$table->varchar('recipient', 255);
			$table->varchar('session', 255);
			$table->text('message');
			$table->text('attachments');
			$table->tinyInteger('priority', 1)->default(0);
			$table->enum('status', ['pending', 'sending', 'sent', 'error'])->default('"pending"');

			// Indexes
			$table->index('status')->fields('status', 'agent_id', 'priority');
			$table->index('priority')->fields('priority', 'agent_id');
			$table->index('dispatch_id')->fields('dispatch_id', 'status');
			$table->index('created_at')->fields('created_at', 'status');
			$table->index('agent_id')->fields('agent_id', 'status');
		});
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->drop('sms_queue');
	}
}
