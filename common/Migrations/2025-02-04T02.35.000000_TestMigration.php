<?php declare(strict_types= 1);

use Proto\Database\Migrations\Migration;

/**
 * Test migration.
 *
 * @package Proto\Database\Migrations
 */
class TestMigration extends Migration
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
		$this->create('test_table', function($table)
		{
			$table->id();
			$table->createdAt();
			$table->updatedAt();
			$table->int('message_id', 20);
			$table->varchar('subject', 160);
			$table->text('message')->nullable();
			$table->datetime('read_at');
			$table->datetime('forwarded_at');

			// indices
			$table->index('email_read')->fields('id', 'read_at');
			$table->index('created')->fields('created_at');

			// foreign keys
			//$table->foreign('message_id')->references('id')->on('messages');
		});

		/**
		 * This will create or replace a view using the query builder.
		 */
		$this->createView('vw_test')
			->table('test_table', 't')
			->select('id', 'created_at')
			->where('id > 1');

		/**
		 * This will create or replace a view using an sql string.
		 */
		$this->createView('vw_test_query')
			->query('
				SELECT id FROM test_table
			');

		$this->alter('test_table', function($table)
		{
			$table->add('status')->int(20);
			$table->alter('subject')->varchar(180);
			$table->drop('read_at');
		});
	}

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->alter('test_table', function($table)
		{
			$table->drop('status');
			$table->alter('subject')->varchar(160);
			$table->add('read_at')->datetime();
		});

		/**
		 * This will drop a view.
		 */
		$this->dropView('vw_test');

		/**
		 * This will drop a view.
		 */
		$this->dropView('vw_test_query');

		$this->drop('test_table');
	}
}