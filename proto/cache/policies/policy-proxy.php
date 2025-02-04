<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

/**
 * PolicyProxy
 *
 * This will create a cache proxy for the policy.
 *
 * @package Proto\Cache\Policies
 */
class PolicyProxy
{
    /**
	 * @var object $controller
	 */
    protected object $controller;

    /**
     * @var object $policy
     */
    protected object $policy;

    /**
     * This will create a cache proxy for the policy.
     *
     * @param object $controller
     * @param object $policy
     * @return void
     */
    public function __construct(object $controller, object $policy)
    {
        $this->controller = $controller;
        $this->policy = $policy;
    }

    /**
     * This will check if the method should be cached.
     *
     * @param string $method
     * @return bool
     */
    protected function shouldCache(string $method): bool
    {
        return $this->isCallable($this->policy, $method);
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
        if ($this->shouldCache($method) === false)
        {
            return $this->callController($method, $arguments);
        }

        return $this->checkCache($method, $arguments);
    }

    /**
     * This will call the controller.
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     */
    protected function callController(string $method, ?array $arguments = []): mixed
    {
        return \call_user_func_array([$this->controller, $method], $arguments);
    }

    /**
     * This will check the policy cache.
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     */
    protected function checkCache(string $method, ?array $arguments = []): mixed
    {
        return $this->callMethod($this->policy, $method, $arguments);
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

    /**
     * This will call an object method.
     *
     * @param object $object
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     */
    protected function callMethod(
        object $object,
        string $method,
        ?array $arguments = []
    ): mixed
    {
        return \call_user_func_array([$object, $method], $arguments);
    }
}
