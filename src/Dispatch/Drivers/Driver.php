<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers;

use Proto\Dispatch\ResponseTrait;

/**
 * Abstract Class Driver
 *
 * Base driver class providing common response functionality.
 *
 * @package Proto\Dispatch\Drivers
 */
abstract class Driver implements DriverInterface
{
	/**
	 * Inherit methods to return responses.
	 */
	use ResponseTrait;
}