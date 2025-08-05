<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Abstract Class Dispatch
 *
 * Base dispatch class to be extended for specific dispatch types.
 *
 * @package Proto\Dispatch
 * @abstract
 */
abstract class Dispatch implements DispatchInterface
{
	/**
	 * Response handling utilities.
	 */
	use ResponseTrait;
}