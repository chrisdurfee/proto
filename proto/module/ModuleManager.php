<?php declare(strict_types=1);
namespace Proto\Module;

use Proto\Module\registerModule;
use Proto\Utils\Strings;

/**
 * ModuleManager class
 *
 * Manages the activation of app modules.
 *
 * @package Proto
 */
class ModuleManager
{
	/**
	 * Converts a fully qualified module class name to a simplified key.
	 *
	 * For example:
	 *  "Modules\Billing\BillingModule" becomes "billing"
	 *
	 * @param string $className
	 * @return string
	 */
	protected static function generateKey(string $className): string
	{
		// Extract the base name without the namespace.
		$parts = explode('\\', $className);
		$simpleName = end($parts); // e.g., "BillingModule"
		if (substr($simpleName, -6) === 'Module')
		{
			$simpleName = substr($simpleName, 0, -6);
		}

		return Strings::camelCase($simpleName);
	}

	/**
	 * Activates the specified modules.
	 *
	 * @param array $modules List of module class names to activate
	 * @return void
	 */
	public static function activate(array $modules): void
	{
		foreach ($modules as $module)
		{
			$className = 'Modules\\' . $module;
			if (class_exists($className))
			{
				$moduleInstance = new $className();
				$moduleInstance->init();

				self::registerGateway($className);
			}
		}
	}

	/**
	 * Registers a module with the given class name.
	 *
	 * @param string $className The class name of the module to register
	 * @return void
	 */
	protected static function registerGateway(string $className)
	{
		// Generate a key from the class name.
		$key = self::generateKey($className);

		// Register the module using a factory callable that returns a new instance.
		registerModule($key, function() use ($key)
		{
			$path = 'Modules\\' . $key . '\\Gateway\\' . $key;
			return new $path();
		});
	}
}