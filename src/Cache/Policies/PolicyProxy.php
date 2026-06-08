<?php declare(strict_types=1);
namespace Proto\Cache\Policies;

use Proto\Controllers\Controller;
use Proto\Controllers\ControllerInterface;

/**
 * PolicyProxy
 *
 * This class creates a cache proxy for the policy.
 *
 * @package Proto\Cache\Policies
 */
class PolicyProxy implements ControllerInterface
{
	/**
	 * Initializes the proxy with the controller and policy objects.
	 *
	 * @param Controller $controller The controller instance.
	 * @param Policy $policy The policy instance.
	 */
	public function __construct(
		protected Controller $controller,
		protected Policy $policy
	)
	{
	}

	/**
	 * Retrieves the policy class name for the wrapped controller.
	 *
	 * Delegates to the underlying controller so the cache proxy can be
	 * transparently wrapped by the auth policy proxy.
	 *
	 * @return string|null
	 */
	public function getPolicy(): ?string
	{
		return $this->controller->getPolicy();
	}

	/**
	 * Determines if the given method should be cached.
	 *
	 * @param string $method The method name.
	 * @return bool True if the method should be cached, otherwise false.
	 */
	protected function shouldCache(string $method): bool
	{
		return $this->isCallable($this->policy, $method);
	}

	/**
	 * Magic method to handle method calls dynamically.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The result of the method call.
	 */
	public function __call(string $method, array $arguments): mixed
	{
		return $this->shouldCache($method)
			? $this->callMethod($this->policy, $method, $arguments)
			: $this->callMethod($this->controller, $method, $arguments);
	}

	/**
	 * Determines if a method is callable on the given object.
	 *
	 * @param object $object The object to check.
	 * @param string $method The method name.
	 * @return bool True if callable, otherwise false.
	 */
	protected function isCallable(object $object, string $method): bool
	{
		return \is_callable([$object, $method]);
	}

	/**
	 * Calls a method on a given object.
	 *
	 * @param object $object The object instance.
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The result of the method call.
	 */
	protected function callMethod(object $object, string $method, array $arguments = []): mixed
	{
		return \call_user_func_array([$object, $method], $arguments);
	}
}