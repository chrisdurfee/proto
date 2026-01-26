<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

use Proto\Database\Database;
use Proto\Database\Adapters\Mysqli;
use Proto\Database\ConnectionCache;
use Proto\Utils\Strings;
use Proto\Config;

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
		// CRITICAL FIX: Clear any existing connection cache before tests
		// This ensures we start fresh and don't reuse a connection from
		// a previous test with different transaction state
		ConnectionCache::clear();

		// CRITICAL FIX: For tests, we must ensure non-persistent connections
		// Persistent connections can maintain autocommit=true across tests,
		// breaking transaction isolation. We need to modify the connection
		// settings BEFORE creating the connection.
		$this->ensureNonPersistentConnection();

		// Get the default connection which will automatically use 'testing' env settings
		// CRITICAL: Use caching=true to ensure this is the SAME connection used by models/storage
		$db = Database::getConnection('default', true);
		if (!$db)
		{
			throw new \RuntimeException('Failed to create test database connection');
		}

		// Disable autocommit for transaction control
		// This MUST happen before any queries are executed
		$db->autoCommit(false);

		$this->testDatabase = $db;
	}

	/**
	 * Ensures test database uses non-persistent connections.
	 * This is critical for proper transaction isolation in tests.
	 *
	 * @return void
	 */
	protected function ensureNonPersistentConnection(): void
	{
		// Modify the config to ensure testing connection is non-persistent
		$config = Config::getInstance();
		$connections = $config->get('connections');
		if (isset($connections->default->testing))
		{
			$connections->default->testing->persistent = false;
		}
		elseif (isset($connections->default))
		{
			// If no specific testing config, modify the default
			if (is_object($connections->default))
			{
				$connections->default->persistent = false;
			}
		}
	}

	/**
	 * Seeds the database with test data.
	 *
	 * @param array $seeders Array of seeder class names
	 * @return void
	 */
	protected function seedDatabase(array $seeders = []): void
	{
		$db = $this->getTestDatabase();

		foreach ($seeders as $seeder)
		{
			if (!class_exists($seeder))
			{
				continue;
			}

			// Check if it's a proper seeder class
			if (is_subclass_of($seeder, 'Proto\Database\Seeders\Seeder'))
			{
				$seederInstance = new $seeder();
				// Pass the test database connection to ensure same transaction
				$seederInstance->setConnection($db);
				$seederInstance->run();
				continue;
			}

			// Legacy support for classes with run() method
			$seederInstance = new $seeder();
			if (method_exists($seederInstance, 'run'))
			{
				$seederInstance->run();
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
	protected function assertDatabaseHas(string $table, array $data, bool $useSnakeCase = true): void
	{
		$db = $this->getTestDatabase();
		$conditions = [];
		$params = [];

		foreach ($data as $column => $value)
		{
			$column = self::prepareColName($column, $useSnakeCase);

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
	 * @param bool $useSnakeCase
	 * @return void
	 */
	protected function assertDatabaseMissing(string $table, array $data, bool $useSnakeCase = true): void
	{
		$db = $this->getTestDatabase();
		$conditions = [];
		$params = [];

		foreach ($data as $column => $value)
		{
			$column = self::prepareColName($column, $useSnakeCase);

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
	 * Prepares the column name.
	 *
	 * @param string $colName
	 * @param bool $useSnakeCase
	 * @return string
	 */
	protected static function prepareColName(string $colName, bool $useSnakeCase): string
	{
		return $useSnakeCase ? Strings::snakeCase($colName) : $colName;
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

			// Clear connection cache after rolling back to ensure
			// the next test gets a fresh connection
			ConnectionCache::clear();

			// Reset test database reference
			$this->testDatabase = null;
		}
	}
}