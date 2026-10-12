<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Config;

/**
 * Class Sms
 *
 * This class sends an SMS message.
 *
 * @package Proto\Dispatch
 */
class Sms extends Dispatch
{
	/**
	 * SMS driver instance.
	 *
	 * @var object|null
	 */
	protected static ?object $driver = null;

	/**
	 * Config instance.
	 *
	 * @var Config|null
	 */
	protected static ?Config $config = null;

	/**
	 * SMS settings.
	 *
	 * @var object
	 */
	protected object $settings;

	/**
	 * Retrieves the application configuration.
	 *
	 * @return Config
	 */
	protected static function getConfig(): Config
	{
		return self::$config ?? (self::$config = Config::getInstance());
	}

	/**
	 * Driver used by this instance (custom or the shared default).
	 *
	 * @var object
	 */
	protected object $instanceDriver;

	/**
	 * Constructor for the Sms class.
	 *
	 * A custom driver applies to this message only; it no longer
	 * replaces the shared default driver for every later send.
	 *
	 * @param object $settings SMS settings.
	 * @param object|null $customDriver Optional custom SMS driver.
	 *
	 * @return void
	 */
	public function __construct(object $settings, ?object $customDriver = null)
	{
		$this->instanceDriver = $customDriver ?? static::getDefaultDriver();
		$this->settings = $settings;
	}

	/**
	 * Gets (and caches) the configured default SMS driver.
	 *
	 * @return object
	 */
	protected static function getDefaultDriver(): object
	{
		if (self::$driver !== null)
		{
			return self::$driver;
		}

		$config = self::getConfig();
		$driverName = $config->sms->driver ?? 'TwilioDriver';
		$className = __NAMESPACE__ . '\\Drivers\\Sms\\' . $driverName;
		return self::$driver = new $className();
	}

	/**
	 * Sends the SMS message.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		return $this->instanceDriver->send($this->settings);
	}
}