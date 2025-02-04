<?php declare(strict_types=1);
namespace Tests\Unit;

use Tests\Test;

/**
 * ExampleUnitTest
 */
class ExampleUnitTest extends Test
{
	/**
     * This will be called when the test is set up.
     *
     * @return void
     */
	protected function setUp(): void
	{
		// do something on setup
	}

	/**
	 * This is an example bool test.
	 *
	 * @return void
	 */
	public function testUnit(): void
	{
		$this->assertTrue(true);
	}

	/**
	 * This is an example has array key test.
	 *
	 * @return void
	 */
	public function testArrayKey(): void
	{
		$this->assertArrayHasKey('key', ['key' => 'value']);
	}

	/**
	 * This is an example has array key test.
	 *
	 * @return void
	 */
	public function testUpdate(): void
	{
		$this->assertArrayHasKey('key', ['key' => 'value']);
	}

	/**
	 * This is an example has array key test.
	 *
	 * @return void
	 */
	public function testTrue(): void
	{
		$this->assertTrue(true);
	}

	/**
     * This will be called when the test is torn down.
     *
     * @return void
     */
	protected function tearDown(): void
	{
		// do something on tear down
	}
}