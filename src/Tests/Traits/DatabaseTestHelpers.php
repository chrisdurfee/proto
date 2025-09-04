<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

use Proto\Database\Database;
use Proto\Database\Adapters\Mysqli;
use Proto\Models\Model;

/**
 * DatabaseTestHelpers
 *
 * Provides database testing utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait DatabaseTestHelpers
{
	/**
	 * @var Mysqli|null $testDatabase
	 */
	protected ?Mysqli $testDatabase = null;

	/**
	 * @var array $truncatedTables
	 */
	protected array $truncatedTables = [];

	/**
	 * Creates and connects to test database.
	 *
	 * @return void
	 */
	protected function createTestDatabase(): void
	{
		$database = new Database();
		$this->testDatabase = $database->connect('testing');
	}

	/**
	 * Seeds the database with test data.
	 *
	 * @param array $seeders Array of seeder class names
	 * @return void
	 */
	protected function seedDatabase(array $seeders = []): void
	{
		foreach ($seeders as $seeder)
		{
			if (class_exists($seeder))
			{
				$seederInstance = new $seeder();
				if (method_exists($seederInstance, 'run'))
				{
					$seederInstance->run();
				}
			}
		}
	}

	/**
	 * Truncates a specific table.
	 *
	 * @param string $table
	 * @return void
	 */
	protected function truncateTable(string $table): void
	{
		$db = $this->getTestDatabase();
		$db->execute("TRUNCATE TABLE `{$table}`");
		$this->truncatedTables[] = $table;
	}

	/**
	 * Truncates all tables that were used during testing.
	 *
	 * @return void
	 */
	protected function truncateAllTables(): void
	{
		$tables = array_unique($this->truncatedTables);
		foreach ($tables as $table)
		{
			$this->truncateTable($table);
		}
		$this->truncatedTables = [];
	}

	/**
	 * Asserts that the database contains the given data.
	 *
	 * @param string $table
	 * @param array $data
	 * @return void
	 */
	protected function assertDatabaseHas(string $table, array $data): void
	{
		$db = $this->getTestDatabase();
		$conditions = [];
		$params = [];

		foreach ($data as $column => $value)
		{
			$conditions[] = "`{$column}` = ?";
			$params[] = $value;
		}

		$query = "SELECT COUNT(*) as count FROM `{$table}` WHERE " . implode(' AND ', $conditions);
		$result = $db->first($query, $params);

		$this->assertGreaterThan(0, $result->count ?? 0,
			"Failed asserting that table [{$table}] contains " . json_encode($data)
		);
	}

	/**
	 * Asserts that the database does not contain the given data.
	 *
	 * @param string $table
	 * @param array $data
	 * @return void
	 */
	protected function assertDatabaseMissing(string $table, array $data): void
	{
		$db = $this->getTestDatabase();
		$conditions = [];
		$params = [];

		foreach ($data as $column => $value)
		{
			$conditions[] = "`{$column}` = ?";
			$params[] = $value;
		}

		$query = "SELECT COUNT(*) as count FROM `{$table}` WHERE " . implode(' AND ', $conditions);
		$result = $db->first($query, $params);

		$this->assertEquals(0, $result->count ?? 0,
			"Failed asserting that table [{$table}] does not contain " . json_encode($data)
		);
	}

	/**
	 * Asserts that the database table has the expected count.
	 *
	 * @param string $table
	 * @param int $count
	 * @return void
	 */
	protected function assertDatabaseCount(string $table, int $count): void
	{
		$db = $this->getTestDatabase();
		$query = "SELECT COUNT(*) as count FROM `{$table}`";
		$result = $db->first($query);

		$this->assertEquals($count, $result->count ?? 0,
			"Failed asserting that table [{$table}] has {$count} records"
		);
	}

	/**
	 * Gets or creates the test database instance.
	 *
	 * @return Mysqli
	 */
	protected function getTestDatabase(): Mysqli
	{
		if ($this->testDatabase === null)
		{
			$this->createTestDatabase();
		}

		return $this->testDatabase;
	}

	/**
	 * Begins a database transaction for test isolation.
	 *
	 * @return void
	 */
	protected function beginDatabaseTransaction(): void
	{
		$db = $this->getTestDatabase();
		$db->execute('START TRANSACTION');
	}

	/**
	 * Rolls back the database transaction.
	 *
	 * @return void
	 */
	protected function rollbackDatabaseTransaction(): void
	{
		$db = $this->getTestDatabase();
		$db->execute('ROLLBACK');
	}

	/**
	 * Cleanup method called after each test.
	 *
	 * @return void
	 */
	protected function cleanupDatabase(): void
	{
		if ($this->testDatabase !== null)
		{
			$this->rollbackDatabaseTransaction();
		}
	}
}