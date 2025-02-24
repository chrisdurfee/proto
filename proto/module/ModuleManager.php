<?php declare(strict_types=1);
namespace Proto\Providers;

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
			}
		}
	}
}