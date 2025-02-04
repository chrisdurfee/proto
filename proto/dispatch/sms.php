<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Config;

/**
 * Sms
 *
 * This will send an sms message.
 *
 * @package Proto\Dispatch
 */
class Sms extends Dispatch
{
	/**
	 * @var object $driver
	 */
	protected static object $driver;

	/**
	 * @var object $config
	 */
	protected static $config;

	/**
	 * @var object $settings
	 */
	protected $settings;

	/**
	 * This will get the app settings.
	 *
	 * @return Config
	 */
	protected static function getConfig(): Config
	{
		return self::$config ?? (self::$config = Config::getInstance());
	}

	/**
	 * This will setup the driver and settings.
	 *
	 * @param object $settings
	 * @param ?object $customDriver
	 * @return void
	 */
	public function __construct(object $settings, ?object $customDriver = null)
	{
		self::setupDriver($customDriver);
		$this->settings = $settings;
	}

	/**
	 * This will setup the sms driver.
	 *
	 * @param ?object $customDriver
	 * @return void
	 */
	protected static function setupDriver(?object $customDriver = null): void
	{
		if (isset($customDriver))
		{
			self::$driver = $customDriver;
			return;
		}

		if (isset(self::$driver))
		{
			return;
		}

		$settings = self::getConfig();

		$driverName = $settings->sms->driver ?? 'TwilioDriver';
		$className = __NAMESPACE__ . '\\Drivers\\Sms\\' . $driverName;
		self::$driver = new $className();
	}

	/**
	 * This will send the dispatch.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		return self::$driver->send($this->settings);
	}
}