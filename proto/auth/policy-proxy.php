<?php declare(strict_types=1);
namespace Proto\Auth;

use Proto\Controllers\Response;
use Proto\Http\Response as HttpResponse;

/**
 * PolicyProxy
 *
 * This will proxy the controller and check the policy before calling the controller.
 *
 * @package Proto\Auth
 */
class PolicyProxy
{
    /**
	 * @var object $instance
	 */
    protected object $controller;

    /**
     * @var object $policy
     */
    protected object $policy;

    /**
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
     * This will check to call the policy before calling the controller.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!$this->isCallable($this->controller, $method))
        {
            return null;
        }

        $result = $this->checkPolicy($method, $arguments);
        if ($result === false)
        {
            // this will show the error response and stop
            $this->showErrorResponse();
        }

        return $this->callControllerMethod($method, $arguments);
    }

    /**
     * This will show an error reponse.
     *
     * @param string|null $message
     * @return void
     */
    protected function showErrorResponse(?string $message = null): void
    {
        $message = $message ?? 'The policy is blocking the user from accessing this action';
        $error = $this->error($message);
        new HttpResponse($error, 403);
        die;
    }

    /**
     * This will call the controller method and check the after policy.
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     */
    protected function callControllerMethod(string $method, ?array $arguments = []): mixed
    {
        $result = \call_user_func_array([$this->controller, $method], $arguments);

        $allow = $this->checkPolicyCustomAfter($method, $result);
        if ($allow === false)
        {
            $this->showErrorResponse();
        }

        $allow = $this->checkPolicyAfter($result);
        if ($allow === false)
        {
            $this->showErrorResponse();
        }

        return $result;
    }

    /**
     * This will check to call the default method.
     *
     * @param array|null $arguments
     * @return bool
     */
    protected function checkPolicyDefault(?array $arguments = []): bool
    {
        return $this->callMethod($this->policy, 'default', $arguments, true);
    }

    /**
     * This will check to call the before method.
     *
     * @param array|null $arguments
     * @return bool
     */
    protected function checkPolicyBefore(?array $arguments = []): bool
    {
        return $this->callMethod($this->policy, 'before', $arguments);
    }

    /**
     * This will check to call the policy method or default.
     *
     * @param string $method
     * @param array|null $arguments
     * @return bool
     */
    protected function checkPolicyMethod(string $method, ?array $arguments = []): bool
    {
        if ($this->isCallable($this->policy, $method) === false)
        {
            return $this->checkPolicyDefault($arguments);
        }

        return $this->callMethod($this->policy, $method, $arguments);
    }

    /**
     * This will check to call the after custom method.
     *
     * @param array|null $arguments
     * @return bool
     */
    protected function checkPolicyCustomAfter(string $method, $result): bool
    {
        $methodName = 'after' . ucfirst($method);
        return $this->callMethod($this->policy, $methodName, [$result], true);
    }

    /**
     * This will check to call the after method.
     *
     * @param mixed $result
     * @return bool
     */
    protected function checkPolicyAfter($result): bool
    {
        return $this->callMethod($this->policy, 'after', [$result], true);
    }

    /**
     * This will check the policy method.
     *
     * @param string $method
     * @param array|null $arguments
     * @return bool
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
     * @param bool $defaultReturn
     * @return bool
     */
    protected function callMethod(
        object $object,
        string $method,
        ?array $arguments = [],
        bool $defaultReturn = false
    ): bool
    {
        if (!$this->isCallable($object, $method))
        {
            return $defaultReturn;
        }

        return \call_user_func_array([$object, $method], $arguments);
    }

    /**
	 * This will create an error response.
	 *
	 * @param string $message
	 * @return object
	 */
	protected function error(string $message = ''): object
	{
		$response = new Response();
		$response->error($message);
		return $response->display();
	}
}
