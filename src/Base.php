<?php declare(strict_types=1);
namespace Proto;

use Proto\Module\ModuleManager;
use Proto\Providers\ServiceManager;

/**
 * Base class
 *
 * Initializes the system and activates services.
 *
 * @package Proto
 */
class Base
{
	/**
	 * @var System $system The system instance
	 */
	protected static System $system;

	/**
	 * Initializes the system and services.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->setupSystem();
	}

	/**
	 * Sets the base path for the application.
	 *
	 * @return void
	 */
	private static function setBasePath(): void
	{
		// Define the base path constant
		if (!defined('BASE_PATH'))
		{
			// When used as a Composer package: vendor/protoframework/proto/src -> ../../../../ (project root)
			$vendorPath = realpath(__DIR__ . '/../../../../');

			// When developing the framework: proto/src -> ../ (framework root)
			$frameworkPath = realpath(__DIR__ . '/../');

			$basePath = (file_exists($vendorPath . '/vendor/autoload.php')) ? $vendorPath : $frameworkPath;
			define('BASE_PATH', $basePath);
		}
	}

	/**
	 * Sets up the base settings and initializes the system.
	 *
	 * @return void
	 */
	private function setupSystem(): void
	{
		if (isset(self::$system))
		{
			return;
		}

		self::setBasePath();

		// Preload Error class to ensure global error() function is available
		class_exists('Proto\Error\Error');

		self::$system = new System();
		ModuleManager::activate(env('modules') ?? []);
		ServiceManager::activate(env('services') ?? []);
	}
}