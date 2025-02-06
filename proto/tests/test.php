<?php declare(strict_types=1);
namespace Proto\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

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
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

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