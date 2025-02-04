<?php declare(strict_types=1);
namespace Tests;

use \PHPUnit\Framework\MockObject\MockObject;

/**
 * MockTrait
 *
 * This will add additional methods to help work with
 * mock objects.
 *
 * @package Tests
 */
trait MockTrait
{
    /**
     * This will create a mock object for a class with only the methods specified.
     * This is being used instead of the built-in createMock() method because intelephense
     * won't recognize methods on mock objects created with createMock().
     *
     * @param mixed $class
     * @param array $methods
     * @param bool $disableConstructor
     * @return MockObject
     */
    private function createCustomMock(
        mixed $class,
        array $methods = [],
        bool $disableConstructor = false
    ): MockObject
    {
        if ($disableConstructor)
        {
            return $this->getMockBuilder($class)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->onlyMethods($methods)
                ->getMock();
        }

        return $this->getMockBuilder($class)
            ->onlyMethods($methods)
            ->getMock();
    }
}