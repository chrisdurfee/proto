<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Config;
use Proto\Dispatch\Response;

/**
 * Controller
 *
 * This will be the base controller for all dispatch controllers.
 *
 * @package Proto\Dispatch\Controllers
 */
abstract class Controller
{
    /**
     * @var Config $config
     */
    protected static Config $config;

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
     * This will send the dispatch.
     *
     * @param object $dispatch
     * @return Response
     */
    public static function send($dispatch): Response
    {
        if (!isset($dispatch))
        {
            return Response::create(false, 'No dispatch is setup.');
        }

        return $dispatch->send();
    }

    /**
     * This will setup a dispatch to enqueue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    abstract public static function enqueue(object $settings, ?object $data): object;

    /**
     * This will send a dispatch.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    abstract public static function dispatch(object $settings, ?object $data): Response;
}