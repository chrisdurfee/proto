<?php declare(strict_types=1);
namespace Proto;

use Proto\Module\ModuleManager;
use Proto\Providers\ServiceManager;

// Define the base path constant
define('BASE_PATH', realpath(__DIR__ . '/../'));

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