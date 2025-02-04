<?php declare(strict_types=1);
namespace Tests\Feature;

use Tests\Test;

/**
 * ExampleFeatureTest
 */
class ExampleFeatureTest extends Test
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
     * This will be called when the test is torn down.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // do something on tear down
    }
}