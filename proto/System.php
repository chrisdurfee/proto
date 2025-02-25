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
	 * The configuration settings.
	 *
	 * @var Config
	 */
	protected Config $settings;

	/**
	 * Sets up the timezone and error reporting.
	 *
	 * @param Config|null $settings Configuration settings
	 * @return void
	 */
	public function __construct(
		?Config $settings = null
	)
	{
		$this->settings = $settings ?? Config::getInstance();
		$this->setupSystem();
	}

	/**
	 * Sets up the system settings.
	 *
	 * @return void
	 */
	protected function setupSystem(): void
	{
		$this->setTimeZone();
		$this->setErrorReporting();
	}

	/**
	 * Sets the timezone based on the configuration settings.
	 *
	 * @return void
	 */
	protected function setTimeZone(): void
	{
		$timezone = $this->settings->timeZone ?? 'UTC';
		date_default_timezone_set($timezone);
	}

	/**
	 * Sets the application error reporting based on the configuration settings.
	 *
	 * @return void
	 */
	protected function setErrorReporting(): void
	{
		$errorReporting = $this->settings->errorReporting;
		Error::enable($errorReporting);
	}
}