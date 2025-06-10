<?php declare(strict_types=1);
namespace Proto\Auth;

use Proto\Controllers\ControllerInterface;
use Proto\Controllers\ResourceController;
use Proto\Controllers\Response;
use Proto\Http\Response as HttpResponse;
use Proto\Auth\Policies\Policy;

/**
 * Class PolicyProxy
 *
 * Proxies a controller and checks policies before executing controller methods.
 *
 * @package Proto\Auth
 */
class PolicyProxy implements ControllerInterface
{
	/**
	 * Initializes the proxy with a controller and its corresponding policy.
	 *
	 * @param ResourceController $controller The controller instance.
	 * @param Policy $policy The policy instance.
	 */
	public function __construct(
		protected readonly ResourceController $controller,
		protected readonly Policy $policy
	) {}

	/**
	 * Proxies method calls to the controller, checking policies beforehand.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The controller method return value or null if unauthorized.
	 */
	public function __call(string $method, array $arguments): mixed
	{
		if (!$this->isMethodCallable($this->controller, $method))
		{
			return null;
		}

		if (!$this->checkPolicy($method, $arguments))
		{
			$this->showErrorResponse();
		}

		return $this->callControllerMethod($method, $arguments);
	}

	/**
	 * Displays an error response and stops execution.
	 *
	 * @param string|null $message The error message.
	 */
	protected function showErrorResponse(?string $message = null): void
	{
		$message ??= 'The policy is blocking the user from accessing this action.';
		$error = $this->error($message);
		new HttpResponse($error, 403);
		exit;
	}

	/**
	 * Calls the controller method and checks post-execution policies.
	 *
	 * @param string $method The method name.
	 * @param array|null $arguments The method arguments.
	 * @return mixed The method return value.
	 */
	protected function callControllerMethod(string $method, ?array $arguments = []): mixed
	{
		$result = \call_user_func_array([$this->controller, $method], $arguments);

		if (!$this->checkPolicyCustomAfter($method, $result) || !$this->checkPolicyAfter($result))
		{
			$this->showErrorResponse();
		}

		return $result;
	}

	/**
	 * Checks the default policy method.
	 *
	 * @param array|null $arguments The method arguments.
	 * @return bool Whether access is allowed.
	 */
	protected function checkPolicyDefault(?array $arguments = []): bool
	{
		return $this->callMethod($this->policy, 'default', $arguments, true);
	}

	/**
	 * Checks the `before` policy method.
	 *
	 * @param array|null $arguments The method arguments.
	 * @return bool Whether access is allowed.
	 */
	protected function checkPolicyBefore(?array $arguments = []): bool
	{
		return $this->callMethod($this->policy, 'before', $arguments);
	}

	/**
	 * Checks the policy method corresponding to the controller action.
	 *
	 * @param string $method The method name.
	 * @param array|null $arguments The method arguments.
	 * @return bool Whether access is allowed.
	 */
	protected function checkPolicyMethod(string $method, ?array $arguments = []): bool
	{
		if (!$this->isMethodCallable($this->policy, $method))
		{
			return $this->checkPolicyDefault($arguments);
		}

		return $this->callMethod($this->policy, $method, $arguments);
	}

	/**
	 * Checks the post-execution policy method specific to an action.
	 *
	 * @param string $method The method name.
	 * @param mixed $result The controller method result.
	 * @return bool Whether the action is allowed.
	 */
	protected function checkPolicyCustomAfter(string $method, mixed $result): bool
	{
		$methodName = 'after' . ucfirst($method);
		return $this->callMethod($this->policy, $methodName, [$result], true);
	}

	/**
	 * Checks the general `after` policy method.
	 *
	 * @param mixed $result The controller method result.
	 * @return bool Whether the action is allowed.
	 */
	protected function checkPolicyAfter(mixed $result): bool
	{
		return $this->callMethod($this->policy, 'after', [$result], true);
	}

	/**
	 * Checks all policy methods before executing a controller action.
	 *
	 * @param string $method The method name.
	 * @param array|null $arguments The method arguments.
	 * @return bool Whether access is allowed.
	 */
	protected function checkPolicy(string $method, ?array $arguments = []): bool
	{
		$result = $this->checkPolicyBefore($arguments);
		if ($result === true)
		{
			return true;
		}

		$result = $this->checkPolicyMethod($method, $arguments);
		if ($result === true)
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks if a method is callable on a given object.
	 *
	 * @param object $object The object.
	 * @param string $method The method name.
	 * @return bool Whether the method is callable.
	 */
	protected function isMethodCallable(object $object, string $method): bool
	{
		return \is_callable([$object, $method]);
	}

	/**
	 * Calls a method on an object if it exists.
	 *
	 * @param object $object The object.
	 * @param string $method The method name.
	 * @param array|null $arguments The method arguments.
	 * @param bool $defaultReturn The default return value if method is not callable.
	 * @return bool The method return value or the default return value.
	 */
	protected function callMethod(
		object $object,
		string $method,
		?array $arguments = [],
		bool $defaultReturn = false
	): bool {
		return $this->isMethodCallable($object, $method)
			? \call_user_func_array([$object, $method], $arguments)
			: $defaultReturn;
	}

	/**
	 * Creates an error response.
	 *
	 * @param string $message The error message.
	 * @return object The error response object.
	 */
	protected function error(string $message = ''): object
	{
		$response = new Response();
		$response->error($message);
		return $response->display();
	}
}