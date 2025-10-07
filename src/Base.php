<?php declare(strict_types=1);
namespace Proto;

use Proto\Module\ModuleManager;
use Proto\Providers\ServiceManager;

// Define the base path constant
if (!defined('BASE_PATH'))
{
	// When used as a Composer package: vendor/protoframework/proto/src -> ../../../../ (project root)
	// When developing the framework: proto/src -> ../ (framework root)
	$vendorPath = realpath(__DIR__ . '/../../../../');
	$frameworkPath = realpath(__DIR__ . '/../');

	$basePath = (file_exists($vendorPath . '/vendor/autoload.php')) ? $vendorPath : $frameworkPath;
	define('BASE_PATH', $basePath);
}

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

		self::$system = new System();
		ModuleManager::activate(env('modules') ?? []);
		ServiceManager::activate(env('services') ?? []);
	}
}