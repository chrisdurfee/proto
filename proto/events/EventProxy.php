<?php declare(strict_types=1);
namespace Proto\Events;

/**
 * Class EventProxy
 *
 * Proxies method calls and publishes events when they are invoked.
 *
 * @package Proto\Events
 */
class EventProxy
{
	/**
	 * Initializes the proxy with a target event name and object.
	 *
	 * @param string $target The target event name.
	 * @param object $object The object to proxy.
	 */
	public function __construct(
        public string $target,
        protected object $object
    )
	{
		$this->target = $target;
		$this->object = $object;
	}

	/**
	 * Dynamically calls methods on the proxied object and publishes an event.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The method return value or null if not callable.
	 */
	public function __call(string $method, array $arguments): mixed
	{
		if (!$this->isCallable($method))
        {
			return null;
		}

		$result = \call_user_func_array([$this->object, $method], $arguments);

		$this->publish($method, (object)[
			'args' => $arguments,
			'data' => $result
		]);

		return $result;
	}

	/**
	 * Constructs the event name based on the target and method.
	 *
	 * @param string $method The method name.
	 * @return string The full event name.
	 */
	protected function getEventName(string $method): string
	{
		return sprintf('%s:%s', $this->target, $method);
	}

	/**
	 * Publishes the event to the `Events` system.
	 *
	 * @param string $method The method name.
	 * @param mixed $payload The event payload.
	 */
	protected function publish(string $method, mixed $payload): void
	{
		Events::update($this->getEventName($method), $payload);
	}

	/**
	 * Checks if a method is callable on the proxied object.
	 *
	 * @param string $method The method name.
	 * @return bool True if callable, false otherwise.
	 */
	protected function isCallable(string $method): bool
	{
		return \is_callable([$this->object, $method]);
	}
}