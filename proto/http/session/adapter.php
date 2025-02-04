<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Patterns\Creational\Singleton;

/**
 * Adapter
 *
 * This will create a session adpater to allow
 * different session types.
 *
 * @package Proto\Http\Session
 * @abstract
 */
abstract class Adapter extends Singleton implements SessionInterface
{
	/**
	 * @var static $instance
	 */
	protected static $instance = null;
}
