<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Dispatch
 *
 * This will create a base dispatch class. This can
 * be used to make additional dispatch types.
 *
 * @package Proto\Dispatch
 * @abstract
 */
abstract class Dispatch implements DispatchInterface
{
	/**
	 * This will inherit methods to return responses.
	 */
	use ResponseTrait;
}