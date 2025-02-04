<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers;

use Proto\Dispatch\ResponseTrait;

/**
 * DriverInterface
 *
 * This is the driver interface.
 *
 * @package Proto\Dispatch\Drivers
 */
interface DriverInterface
{

}

/**
 * Driver
 *
 * This will create a driver class.
 *
 * @package Proto\Dispatch\Drivers
 * @abstract
 */
abstract class Driver implements DriverInterface
{
    /**
     * This will inherit methods to return responses.
     */
    use ResponseTrait;
}