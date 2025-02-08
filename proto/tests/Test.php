<?php declare(strict_types=1);
namespace Proto\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Proto\Base;

/**
 * Abstract Test Class
 *
 * Serves as the base class for all test cases.
 * Ensures the system is properly initialized before running tests.
 *
 * @package Proto\Tests
 */
abstract class Test extends TestCase
{
	/**
	 * Initializes the test case.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->setupSystem();
	}

	/**
	 * Sets up the system before tests run.
	 * Can be overridden by child test classes if needed.
	 *
	 * @return void
	 */
	protected function setupSystem(): void
	{
		new Base();
	}
}