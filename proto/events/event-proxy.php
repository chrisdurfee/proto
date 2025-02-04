<?php declare(strict_types=1);
namespace Proto\Events;

/**
 * EventProxy
 *
 * This will proxy events.
 *
 * @package Proto\Events
 */
class EventProxy
{
    /**
     * @var string $target
     */
    public string $target;

    /**
	 * @var object $object
	 */
    protected object $object;

    /**
     *
     * @param string $target
     * @param object $object
     * @return void
     */
    public function __construct(string $target, object $object)
    {
        $this->target = $target;
        $this->object = $object;
    }

    /**
     * This will check to call the policy before calling the controller.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!$this->isCallable($this->object, $method))
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
     * This will get the event name.
     *
     * @param string $method
     * @return string
     */
    protected function getEventName(string $method): string
    {
        return $this->target . ':'. $method;
    }

    /**
     * This will publish the event.
     *
     * @param string $method
     * @param mixed $payload
     * @return void
     */
    protected function publish(string $method, mixed $payload): void
    {
        $name = $this->getEventName($method);
        Events::update($name, $payload);
    }

    /**
     * This will check if a method is callable.
     *
     * @param object $object
     * @param string $method
     * @return bool
     */
    protected function isCallable(object $object, string $method): bool
    {
        return \is_callable([$object, $method]);
    }
}
