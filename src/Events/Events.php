<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Patterns\Creational\Singleton;
use Proto\Patterns\Structural\PubSub;

/**
 * Class Events
 *
 * Provides a Singleton-based event system for subscribing, emitting, and removing events.
 *
 * @package Proto\Events
 */
class Events extends Singleton
{
	/**
	 * @var self|null The singleton instance.
	 */
	protected static ?self $instance = null;

	/**
	 * Initializes the PubSub instance.
	 *
	 * @param PubSub $pubSub The PubSub instance to use.
	 */
	protected function __construct(
		protected PubSub $pubSub = new PubSub()
	)
	{
	}

	/**
	 * Publishes an event.
	 *
	 * @param string $key The event identifier.
	 * @param mixed $payload The event data.
	 */
	public function emit(string $key, mixed $payload): void
	{
		$this->pubSub->publish($key, $payload);
	}

	/**
	 * Subscribes to an event.
	 *
	 * @param string $key The event identifier.
	 * @param callable $callback The function to execute when the event is triggered.
	 * @return string|null The subscription token or null if failed.
	 */
	public function subscribe(string $key, callable $callback): ?string
	{
		return $this->pubSub->subscribe($key, $callback);
	}

	/**
	 * Unsubscribes from an event.
	 *
	 * @param string $key The event identifier.
	 * @param string $token The subscription token to remove.
	 */
	public function unsubscribe(string $key, string $token): void
	{
		$this->pubSub->unsubscribe($key, $token);
	}

	/**
	 * Publishes an event (static wrapper for `emit()`).
	 *
	 * @param string $key The event identifier.
	 * @param mixed $payload The event data.
	 */
	public static function update(string $key, mixed $payload): void
	{
		static::getInstance()->emit($key, $payload);
	}

	/**
	 * Subscribes to an event (static wrapper for `subscribe()`).
	 *
	 * @param string $key The event identifier.
	 * @param callable $callback The function to execute when the event is triggered.
	 * @return string|null The subscription token or null if failed.
	 */
	public static function on(string $key, callable $callback): ?string
	{
		return static::getInstance()->subscribe($key, $callback);
	}

	/**
	 * Unsubscribes from an event (static wrapper for `unsubscribe()`).
	 *
	 * @param string $key The event identifier.
	 * @param string $token The subscription token to remove.
	 */
	public static function off(string $key, string $token): void
	{
		static::getInstance()->unsubscribe($key, $token);
	}
}