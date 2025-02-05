<?php declare(strict_types=1);
namespace Proto;

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
	 *
	 * @param Config $config The configuration instance
	 */
	public function __construct(Config $config)
	{
		$this->setupSystem($config);
	}

	/**
	 * Sets up the system with the given configuration.
	 *
	 * @param Config $config The configuration instance
	 * @return void
	 */
	private function setupSystem(Config $config): void
	{
		self::$system = new System($config);
		ServiceManager::activate($config->services ?? []);
	}
}