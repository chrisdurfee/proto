<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Patterns\Creational\Singleton;
use Proto\Patterns\Structural\PubSub;

/**
 * Events
 *
 * This will allow events to be emitted and listened for.
 *
 * @package Proto\Events
 */
class Events extends Singleton
{
	/**
	 * @var Events $instance
	 */
    protected static $instance = null;

    /**
     * @var PubSub $pubSub
     */
    protected PubSub $pubSub;

	/**
	 * This will setup the PubSub to allow events to
	 * be added, removed, and published.
	 *
	 * @return void
	 */
	protected function __construct()
    {
		$this->setupPubSub();
	}

	/**
	 * This will setup the pub sub.
	 *
	 * @return void
	 */
	protected function setupPubSub(): void
	{
		$this->pubSub = new PubSub();
    }

    /**
     * This will publish an event.
     *
     * @param string $key
     * @param mixed $payload
     * @return void
     */
    public function set(string $key, mixed $payload): void
    {
        $this->pubSub->publish($key, $payload);
    }

    /**
	 * This will add a subscribder.
	 *
	 * @param string $key
	 * @param callable $callBack
	 * @return string|null
	 */
	public function add(string $key, callable $callBack): ?string
	{
		if (!isset($key))
		{
			return null;
		}

		return $this->pubSub->subscribe($key, $callBack);
	}

	/**
	 * This will remove a subscriber
	 *
     * @param string $key
	 * @param string $token
	 * @return void
	 */
	public function remove(string $key, string $token): void
	{
		if (!isset($key))
		{
			return;
		}

		$this->pubSub->unsubscribe($key, $token);
    }

    /**
     * This will publish an event.
     *
     * @param string $key
     * @param mixed $payload
     * @return void
     */
    public static function update(string $key, $payload): void
    {
        $events = static::getInstance();
        $events->set($key, $payload);
    }

	/**
	 * This will add a subscribder.
	 *
	 * @param string $key
	 * @param callable $callBack
	 * @return string|null
	 */
	public static function on(string $key, $callBack): ?string
	{
        $events = static::getInstance();
        return $events->add($key, $callBack);
	}

	/**
	 * This will remove a subscriber
	 *
     * @param string $key
	 * @param string $token
	 * @return void
	 */
	public static function off(string $key, string $token): void
	{
		$events = static::getInstance();
        $events->remove($key, $token);
	}
}