<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Patterns\Structural\PubSub;

/**
 * Class EventEmitter
 *
 * Provides event-based PubSub functionality, allowing events to be emitted, listened for, and removed.
 *
 * @package Proto\Events
 */
class EventEmitter
{
	/**
	 * @var PubSub Handles event subscription and publication.
	 */
	protected PubSub $pubSub;

	/**
	 * Initializes the PubSub instance.
	 */
	public function __construct()
	{
		$this->pubSub = new PubSub();
	}

	/**
	 * Publishes an event.
	 *
	 * @param string $key The event identifier.
	 * @param mixed $payload The event data.
	 */
	public function emit(string $key, mixed $payload = null): void
	{
		$this->pubSub->publish($key, $payload);
	}

	/**
	 * Adds an event listener.
	 *
	 * @param string $key The event identifier.
	 * @param callable $callBack The function to execute when the event is triggered.
	 * @return string|null The subscription token or null if failed.
	 */
	public function on(string $key, callable $callBack): ?string
	{
		return $this->pubSub->subscribe($key, $callBack);
	}

	/**
	 * Removes an event subscriber.
	 *
	 * @param string $key The event identifier.
	 * @param string $token The subscription token to remove.
	 */
	public function off(string $key, string $token): void
	{
		$this->pubSub->unsubscribe($key, $token);
	}
}