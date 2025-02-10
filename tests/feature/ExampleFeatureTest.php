<?php declare(strict_types=1);
namespace Tests\Feature;

use Tests\Test;

/**
 * ExampleFeatureTest
 *
 * Feature tests for examples.
 *
 * @package Tests\Feature
 */
final class ExampleFeatureTest extends Test
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
	 * Called when the test is torn down.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		// do something on tear down
	}
}