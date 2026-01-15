<?php declare(strict_types=1);
namespace Proto\Tests\Unit;

use Proto\Tests\Test;
use Proto\Database\Database;
use Proto\Database\ConnectionCache;

/**
 * TransactionIsolationTest
 *
 * Verifies that database connection caching and transaction isolation
 * work correctly in tests.
 *
 * @package Proto\Tests\Unit
 */
final class TransactionIsolationTest extends Test
{
	/**
	 * Test that dbCaching is enabled during tests.
	 *
	 * @return void
	 */
	public function testDbCachingIsEnabled(): void
	{
		$this->assertTrue(
			(bool)env('dbCaching'),
			'dbCaching should be enabled for test transaction isolation'
		);
	}

	/**
	 * Test that Database::getConnection returns the same instance when caching is enabled.
	 *
	 * @return void
	 */
	public function testConnectionReuse(): void
	{
		// Get two connections with caching enabled
		$db1 = Database::getConnection('default', true);
		$db2 = Database::getConnection('default', true);

		// They should be the exact same instance
		$this->assertSame(
			$db1,
			$db2,
			'Multiple calls to getConnection should return the same cached instance'
		);
	}

	/**
	 * Test that the connection cache is working.
	 *
	 * @return void
	 */
	public function testConnectionCacheContainsDefaultConnection(): void
	{
		// Get a connection (which should cache it)
		$db = Database::getConnection('default', true);

		// Verify it's in the cache
		$this->assertTrue(
			ConnectionCache::has('default'),
			'Connection should be stored in ConnectionCache'
		);

		// Verify cached connection is the same instance
		$cached = ConnectionCache::get('default');
		$this->assertSame(
			$db,
			$cached,
			'Cached connection should be the same instance'
		);
	}

	/**
	 * Test that test database is the same as the one from Database::getConnection.
	 *
	 * @return void
	 */
	public function testTestDatabaseUsesCache(): void
	{
		// Get the test database connection
		$testDb = $this->getTestDatabase();

		// Get connection normally
		$normalDb = Database::getConnection('default', true);

		// They should be the same instance
		$this->assertSame(
			$testDb,
			$normalDb,
			'Test database and normal database connection should be the same instance'
		);
	}

	/**
	 * Test that we're in a transaction during tests.
	 *
	 * @return void
	 */
	public function testTransactionIsActive(): void
	{
		$db = $this->getTestDatabase();

		// Check if we're in a transaction (MySQL specific)
		// This will return 1 if we're in a transaction
		$result = $db->first("SELECT @@in_transaction as active");

		$this->assertEquals(
			1,
			$result->active ?? 0,
			'Should be in an active transaction during tests'
		);
	}

	/**
	 * Test that autocommit is disabled.
	 *
	 * @return void
	 */
	public function testAutocommitIsDisabled(): void
	{
		$db = $this->getTestDatabase();

		// Check autocommit status
		$result = $db->first("SELECT @@autocommit as autocommit");

		$this->assertEquals(
			0,
			$result->autocommit ?? 1,
			'Autocommit should be disabled for transaction control'
		);
	}

	/**
	 * Test that environment is set to testing.
	 *
	 * @return void
	 */
	public function testEnvironmentIsTesting(): void
	{
		$this->assertEquals(
			'testing',
			env('env'),
			'Environment should be set to testing'
		);

		$this->assertEquals(
			'testing',
			$_ENV['APP_ENV'] ?? '',
			'APP_ENV should be set to testing'
		);
	}

	/**
	 * Test basic transaction isolation with data.
	 * This test creates data that should be rolled back automatically.
	 *
	 * @return void
	 */
	public function testDataIsIsolatedBetweenTests(): void
	{
		$db = $this->getTestDatabase();

		// Create a test table
		$db->execute("
			CREATE TABLE IF NOT EXISTS test_isolation (
				id INT AUTO_INCREMENT PRIMARY KEY,
				value VARCHAR(255)
			)
		");

		// Insert test data
		$db->execute("INSERT INTO test_isolation (value) VALUES (?)", ['test_value']);

		// Verify data exists
		$result = $db->first("SELECT COUNT(*) as count FROM test_isolation");
		$this->assertEquals(1, $result->count);

		// Data will be rolled back automatically after this test
	}

	/**
	 * This test runs after testDataIsIsolatedBetweenTests.
	 * If transaction isolation works, the table should be empty.
	 *
	 * @return void
	 */
	public function testPreviousTestDataWasRolledBack(): void
	{
		$db = $this->getTestDatabase();

		// The test_isolation table might not exist if previous test was rolled back
		// Try to create it again
		$db->execute("
			CREATE TABLE IF NOT EXISTS test_isolation (
				id INT AUTO_INCREMENT PRIMARY KEY,
				value VARCHAR(255)
			)
		");

		// Check if data from previous test exists
		$result = $db->first("SELECT COUNT(*) as count FROM test_isolation");

		// Should be 0 if transaction isolation worked
		$this->assertEquals(
			0,
			$result->count ?? 0,
			'Data from previous test should have been rolled back'
		);

		// Clean up
		$db->execute("DROP TABLE IF EXISTS test_isolation");
	}
}
