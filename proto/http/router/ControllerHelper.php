<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Auth\PolicyProxy;
use Proto\Cache\Cache;
use Proto\Cache\Policies\ModelPolicy;
use Proto\Cache\Policies\PolicyProxy as CacheProxy;
use Proto\Controllers\Controller;
use Proto\Controllers\ControllerInterface;

/**
 * ControllerHelper
 *
 * This class provides helper methods for managing controllers and their associated policies.
 *
 * @package Proto\Http\Router
 */
class ControllerHelper
{
	/**
	 * This will set the caching policy for the controller.
	 *
	 * @param Controller $controller
	 * @param string $policy
	 * @return mixed
	 */
	protected static function setCachingPolicy(
		Controller $controller,
		string $policy = ModelPolicy::class
	): mixed
	{
		if (Cache::isSupported() !== true)
		{
			return $controller;
		}

		/**
		 * @var object $cachePolicy
		 */
		$cachePolicy = new $policy($controller);
		return new CacheProxy($controller, $cachePolicy);
	}

	/**
	 * This will get the controller. If the controller has a policy
	 * defined, it will create a policy proxy to auth the actions
	 * before calling the methods.
	 *
	 * @param ControllerInterface|string $controller
	 * @return ControllerInterface
	 */
	public static function getController(ControllerInterface|string $controller): ControllerInterface
	{
		if (is_string($controller) === true)
		{
			$controller = new $controller();
		}

		/**
		 * This will set up a caching policy for the controller.
		 */
		$controller = static::setCachingPolicy($controller);

		/**
		 * This will check if the controller has a policy defined.
		 */
		$policy = $controller->getPolicy();
		if (!isset($policy))
		{
			return $controller;
		}

		/**
		 * This will create a policy proxy to auth the actions
		 * before calling the methods.
		 */
		return new PolicyProxy($controller, new $policy($controller));
	}
}