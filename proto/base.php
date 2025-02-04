<?php declare(strict_types=1);
namespace Proto;

// Define the base path constant
define('BASE_PATH', realpath(__DIR__ . '/../'));

/**
 * Base class
 *
 * This class sets up the system and activates services.
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
	 * This will set up the system and activate services.
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

		$settings = $this->getConfig();
		self::$system = new System($settings);

		$services = $settings->services ?? [];
		$this->activateServices($services);
	}

	/**
	 * Sets up and initializes the framework services.
	 *
	 * @param array $services An array of service class names to activate
	 * @return void
	 */
	private function activateServices(array $services = []): void
	{
		if (count($services) < 1)
		{
			return;
		}

		foreach ($services as $service)
		{
			$className = 'App\\Providers\\' . $service;
			$module = new $className();
			$module->init();
		}
	}

	/**
	 * Returns the Config settings.
	 *
	 * @return Config The Config instance
	 */
	public function getConfig(): Config
	{
		return Config::getInstance();
	}
}
