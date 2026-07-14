<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration to make the proto_error_log.error_ip column nullable.
 *
 * CLI contexts have no request IP, so Request::ip() returns null. Without a
 * nullable column, those error inserts fail silently and the underlying error
 * text is lost. This makes error_ip nullable so CLI errors are captured.
 *
 * @package Proto\Database\Migrations
 * @suppresswarnings PHP6609
 */
class ErrorLogIpNullable extends Migration
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
		$this->alter('proto_error_log', function($table)
		{
			$table->alter('error_ip')->varchar(45)->nullable();
		});
	}

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$this->alter('proto_error_log', function($table)
		{
			$table->alter('error_ip')->varchar(45);
		});
	}
}
