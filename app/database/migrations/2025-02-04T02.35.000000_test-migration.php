<?php

use Proto\Database\Migrations\Migration;

class TestMigration extends Migration
{
    /**
     * @var string $connection The database connection name.
     */
    protected $connection = 'default';

    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        $this->create('test_table', function($table)
        {
            $table->id();
			$table->createdAt();
			$table->updatedAt();
			$table->int('message_id', 20);
			$table->varchar('subject', 160);
			$table->text('message')->null();
			$table->dateTime('read_at');
			$table->dateTime('forwarded_at');

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
    public function down()
    {
        $this->alter('test_table', function($table)
        {
            $table->drop('status');
            $table->alter('subject')->varchar(160);
            $table->add('read_at')->dateTime();
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