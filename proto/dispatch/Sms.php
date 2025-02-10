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
	 * Constructor for the Sms class.
	 *
	 * @param object      $settings     SMS settings.
	 * @param object|null $customDriver Optional custom SMS driver.
	 *
	 * @return void
	 */
	public function __construct(object $settings, ?object $customDriver = null)
	{
		self::setupDriver($customDriver);
		$this->settings = $settings;
	}

	/**
	 * Sets up the SMS driver.
	 *
	 * @param object|null $customDriver Optional custom driver instance.
	 *
	 * @return void
	 */
	protected static function setupDriver(?object $customDriver = null): void
	{
		if ($customDriver !== null)
		{
			self::$driver = $customDriver;
			return;
		}

		if (self::$driver !== null)
		{
			return;
		}

		$config = self::getConfig();
		$driverName = $config->sms->driver ?? 'TwilioDriver';
		$className = __NAMESPACE__ . '\\Drivers\\Sms\\' . $driverName;
		self::$driver = new $className();
	}

	/**
	 * Sends the SMS message.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		return self::$driver->send($this->settings);
	}
}