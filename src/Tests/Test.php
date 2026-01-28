<?php declare(strict_types=1);
namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Base;
use Proto\Tests\Traits\DatabaseTestHelpers;
use Proto\Tests\Traits\ModelTestHelpers;
use Proto\Tests\Traits\HttpTestHelpers;
use Proto\Tests\Traits\AssertionHelpers;
use Proto\Tests\Traits\TestDataHelpers;
use Proto\Tests\Traits\MockHelpers;
use Proto\Tests\Traits\FileTestHelpers;
use Proto\Error\Error;

/**
 * Abstract Test Class
 *
 * Serves as the base class for all test cases.
 * Ensures the system is properly initialized before running tests.
 * Provides comprehensive testing utilities through traits.
 *
 * IMPORTANT: Uses database transactions for test isolation by default.
 * All database changes are automatically rolled back after each test.
 *
 * @see docs/TESTING_BEST_PRACTICES.md
 * @see docs/TEST_QUICK_REFERENCE.md
 * @package Proto\Tests
 */
abstract class Test extends TestCase
{
	use DatabaseTestHelpers,
		ModelTestHelpers,
		HttpTestHelpers,
		AssertionHelpers,
		TestDataHelpers,
		MockHelpers,
		FileTestHelpers;

	/**
	 * @var bool $useTransactions Whether to use database transactions for isolation
	 */
	protected bool $useTransactions = true;

	/**
	 * @var array $seeders Database seeders to run before tests
	 */
	protected array $seeders = [];

	/**
	 * Initializes the test case.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->setupSystem();
		$this->setupTestEnvironment();
	}

	/**
	 * Cleans up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->cleanupTestEnvironment();
		parent::tearDown();
	}

	/**
	 * Sets up the system before tests run.
	 * Can be overridden by child test classes if needed.
	 *
	 * @return void
	 */
	protected function setupSystem(): void
	{
		/**
		 * CRITICAL: Set the env to testing BEFORE initializing Base
		 * This ensures the testing database/cache connections are used
		 */
		$_SERVER['APP_ENV'] = 'testing';
		$_ENV['APP_ENV'] = 'testing';

		new Base();

		// Suppress error output during tests (must be AFTER Base initializes env())
		Error::silent();

		/**
		 * This will set the env to testing to use the testing connection settings.
		 */
		setEnv('env', 'testing');
	}

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	protected function setupTestEnvironment(): void
	{
		// Only setup database if BASE_PATH is defined (framework is initialized)
		if (defined('BASE_PATH'))
		{
			// CRITICAL: Enable database connection caching for tests
			// This ensures ALL database connections return the same instance
			// which is essential for transaction isolation
			setEnv('dbCaching', true);

			// Setup database if needed
			if ($this->useTransactions)
			{
				$this->beginDatabaseTransaction();
			}

			// Run seeders
			if (!empty($this->seeders))
			{
				$this->seedDatabase($this->seeders);
			}
		}

		// Reset HTTP state (doesn't require BASE_PATH)
		$this->resetHttpState();
	}

	/**
	 * Cleans up the test environment.
	 *
	 * @return void
	 */
	protected function cleanupTestEnvironment(): void
	{
		// Only cleanup database if BASE_PATH is defined
		if (defined('BASE_PATH'))
		{
			// Cleanup database
			if ($this->useTransactions)
			{
				$this->cleanupDatabase();
			}

			// Cleanup models
			$this->cleanupModels();
		}

		// Cleanup temporary files
		$this->cleanupTempFiles();

		// Cleanup test files
		$this->cleanupTestFiles();

		// Clear mocks
		$this->clearMocks();

		// Reset HTTP state
		$this->resetHttpState();
	}

	/**
	 * Refreshes the application state.
	 *
	 * @return void
	 */
	protected function refreshApplication(): void
	{
		$this->setupSystem();
	}

	/**
	 * Enables or disables database transactions for this test.
	 *
	 * @param bool $enabled
	 * @return void
	 */
	protected function setUseTransactions(bool $enabled): void
	{
		$this->useTransactions = $enabled;
	}

	/**
	 * Sets the seeders to run before tests.
	 *
	 * @param array $seeders
	 * @return void
	 */
	protected function setSeeders(array $seeders): void
	{
		$this->seeders = $seeders;
	}

	/**
	 * Helper method to quickly create test data.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function withTestData(array $data): void
	{
		foreach ($data as $key => $value)
		{
			$this->setTestData($key, $value);
		}
	}
}