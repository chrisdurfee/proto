<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Config;
use Proto\Dispatch\Response;

/**
 * Class Controller
 *
 * Base controller for all dispatch controllers.
 *
 * @package Proto\Dispatch\Controllers
 */
abstract class Controller
{
	/**
	 * App configuration.
	 *
	 * @var Config
	 */
	protected static Config $config;

	/**
	 * Gets the app configuration.
	 *
	 * @return Config
	 */
	protected static function getConfig(): Config
	{
		return self::$config ?? (self::$config = Config::getInstance());
	}

	/**
	 * Sends the dispatch.
	 *
	 * @param object $dispatch The dispatch object.
	 * @return Response
	 */
	public static function send(object $dispatch): Response
	{
		if (!isset($dispatch))
		{
			return Response::create(false, 'No dispatch is setup.');
		}

		return $dispatch->send();
	}

	/**
	 * Sets up a dispatch to enqueue.
	 *
	 * @param object $settings The dispatch settings.
	 * @param object|null $data Additional data for the dispatch.
	 * @return object
	 */
	abstract public static function enqueue(object $settings, ?object $data): object;

	/**
	 * Sends a dispatch.
	 *
	 * @param object $settings The dispatch settings.
	 * @param object|null $data Additional data for the dispatch.
	 * @return Response
	 */
	abstract public static function dispatch(object $settings, ?object $data): Response;
}