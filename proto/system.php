<?php declare(strict_types=1);
namespace Proto;

use Proto\Error\Error;

/**
 * System Class
 *
 * Handles the setup of system settings, such as timezone and error reporting.
 *
 * @package Proto
 */
class System
{
	/**
	 * Sets up the timezone and error reporting.
	 *
	 * @param Config $settings Configuration settings
	 * @return void
	 */
	public function __construct(Config $settings = Config::getInstance())
	{
		$this->setupSystem($settings);
	}

	/**
	 * Sets up the system settings.
	 *
	 * @param object $settings Configuration settings
	 * @return void
	 */
	protected function setupSystem(object $settings): void
	{
		$this->setTimeZone($settings);
		$this->setErrorReporting($settings);
	}

	/**
	 * Sets the timezone based on the configuration settings.
	 *
	 * @param object $settings Configuration settings
	 * @return void
	 */
	protected function setTimeZone(object $settings): void
	{
		date_default_timezone_set($settings->timeZone);
	}

	/**
	 * Sets the application error reporting based on the configuration settings.
	 *
	 * @param object $settings Configuration settings
	 * @return void
	 */
	protected function setErrorReporting(object $settings): void
	{
		$errorReporting = $settings->errorReporting;
		Error::enable($errorReporting);
	}
}