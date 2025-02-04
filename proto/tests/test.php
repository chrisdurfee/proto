<?php declare(strict_types=1);
namespace Proto\Tests;

use PHPUnit\Framework\TestCase;
use Proto\Base;

/**
 * Test class
 *
 * This class sets up the test base class.
 *
 * @package Proto\Tests
 */
abstract class Test extends TestCase
{
    /**
     * This will be called when the test is set up.
     *
     * @param string|null $name
     * @param array $data
     * @param string|int $dataName
     */
    public function __construct(?string $name = null, array $data = [], string|int $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Set up the system
        $this->setupSystem();
    }

    /**
     * Sets up the base settings and initializes the system.
     *
     * @return void
     */
    private function setupSystem(): void
    {
        new Base();
    }
}