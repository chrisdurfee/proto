<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Patterns\Creational\Singleton;

/**
 * Adapter
 *
 * Defines a session adapter to support different session types.
 *
 * @package Proto\Http\Session
 * @abstract
 */
abstract class Adapter extends Singleton implements SessionInterface
{
	/**
	 * Singleton instance of the adapter.
	 *
	 * @var static|null
	 */
	protected static ?self $instance = null;
}