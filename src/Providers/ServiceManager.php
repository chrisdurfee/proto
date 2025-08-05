<?php declare(strict_types=1);
namespace Proto\Providers;

/**
 * ServiceManager class
 *
 * Manages the activation of framework services.
 *
 * @package Proto
 */
class ServiceManager
{
	/**
	 * Activates the specified services.
	 *
	 * @param array $services List of service class names to activate
	 * @return void
	 */
	public static function activate(array $services): void
	{
		foreach ($services as $service)
		{
			$className = $service;
			if (class_exists($className))
            {
				$module = new $className();
				$module->init();
			}
		}
	}
}