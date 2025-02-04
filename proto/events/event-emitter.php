<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Patterns\Structural\PubSub;

/**
 * EventEmitter
 *
 * This will allow events to be emitted and listened for.
 *
 * @package Proto\Events
 */
class EventEmitter
{
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
	public function __construct()
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
    public function emit(string $key, mixed $payload = null): void
    {
        $this->pubSub->publish($key, $payload);
    }

	/**
	 * This will add ane event listener.
	 *
	 * @param string $key
	 * @param callable $callBack
	 * @return string|null
	 */
	public function on(string $key, callable $callBack): ?string
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
	public function off(string $key, string $token): void
	{
		if (!isset($key))
		{
			return;
		}

		$this->pubSub->unsubscribe($key, $token);
	}
}