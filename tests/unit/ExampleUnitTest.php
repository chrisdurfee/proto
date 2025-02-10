<?php declare(strict_types=1);
namespace Tests\Unit;

use Tests\Test;

/**
 * ExampleUnitTest
 *
 * Unit tests for examples.
 */
final class ExampleUnitTest extends Test
{
	/**
	 * Called when the test is set up.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		// do something on setup
	}

	/**
	 * Example bool test.
	 *
	 * @return void
	 */
	public function testUnit(): void
	{
		$this->assertTrue(true);
	}

	/**
	 * Example test for array key existence.
	 *
	 * @return void
	 */
	public function testArrayKey(): void
	{
		$this->assertArrayHasKey('key', ['key' => 'value']);
	}

	/**
	 * Example update test.
	 *
	 * @return void
	 */
	public function testUpdate(): void
	{
		$this->assertArrayHasKey('key', ['key' => 'value']);
	}

	/**
	 * Example true test.
	 *
	 * @return void
	 */
	public function testTrue(): void
	{
		$this->assertTrue(true);
	}

	/**
	 * Called when the test is torn down.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		// do something on tear down
	}
}