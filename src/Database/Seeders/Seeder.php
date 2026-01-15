<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

use Proto\Database\Database;
use Proto\Database\Adapters\Mysqli;

/**
 * Seeder
 *
 * Abstract base class for database seeders.
 * Provides common functionality for seeding test and initial data.
 *
 * @package Proto\Database\Seeders
 */
abstract class Seeder
{
	/**
	 * @var Database|null $database
	 */
	protected ?Database $database = null;

	/**
	 * @var Mysqli|null $externalConnection
	 */
	protected ?Mysqli $externalConnection = null;

	/**
	 * Connection key.
	 * @var string
	 */
	protected string $connection = 'default';

	/**
	 * Run the database seeder.
	 *
	 * @return void
	 */
	abstract public function run(): void;

	/**
	 * Set an external database connection to use.
	 *
	 * @param Mysqli $connection
	 * @return void
	 */
	public function setConnection(Mysqli $connection): void
	{
		$this->externalConnection = $connection;
	}

	/**
	 * Gets the database instance.
	 *
	 * @return Database
	 */
	protected function getDatabase(): Database
	{
		if ($this->database === null)
		{
			$this->database = new Database();
		}
		return $this->database;
	}

	/**
	 * Gets a database connection.
	 *
	 * @param string|null $connection
	 * @return Mysqli|null
	 */
	protected function getConnection(?string $connection = null): ?Mysqli
	{
		// Use external connection if set (for testing)
		if ($this->externalConnection !== null)
		{
			return $this->externalConnection;
		}

		$connection = $connection ?? $this->connection;
		// Use static method with caching enabled for test isolation
		return Database::getConnection($connection, true);
	}

	/**
	 * Inserts data into a table.
	 *
	 * @param string $table
	 * @param array $data
	 * @return void
	 */
	protected function insert(string $table, array $data): void
	{
		$db = $this->getConnection();

		foreach ($data as $row)
		{
			$db->insert($table, $row);
		}
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table
	 * @return void
	 */
	protected function truncate(string $table): void
	{
		$db = $this->getConnection();
		$db->execute("TRUNCATE TABLE `{$table}`");
	}

	/**
	 * Checks if a table is empty.
	 *
	 * @param string $table
	 * @return bool
	 */
	protected function isEmpty(string $table): bool
	{
		$db = $this->getConnection();
		$result = $db->first("SELECT COUNT(*) as count FROM `{$table}`");
		return ($result->count ?? 0) == 0;
	}

	/**
	 * Calls another seeder.
	 *
	 * @param string $seederClass
	 * @return void
	 */
	protected function call(string $seederClass): void
	{
		if (class_exists($seederClass))
		{
			$seeder = new $seederClass();
			$seeder->run();
		}
	}

	/**
	 * Calls multiple seeders.
	 *
	 * @param array $seederClasses
	 * @return void
	 */
	protected function callMany(array $seederClasses): void
	{
		foreach ($seederClasses as $seederClass)
		{
			$this->call($seederClass);
		}
	}
}