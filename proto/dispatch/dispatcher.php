<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Controllers;

/**
 * Dispatcher
 *
 * This will dispatch messages by email, sms,
 * and push notification.
 *
 * @package Proto\Dispatch
 */
class Dispatcher
{
    /**
     * This will send the dispatch.
     *
     * @param DispatchInterface $dispatch
     * @return Response
     */
    public static function send(DispatchInterface $dispatch): Response
    {
        if (!isset($dispatch))
        {
            return Response::create(false, 'No dispatch is setup.');
        }

        return $dispatch->send();
    }

    /**
     * This will send an sms message.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    public static function sms(object $settings, ?object $data = null): Response
    {
        return Controllers\TextController::dispatch($settings, $data);
    }

    /**
     * This will send an email.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    public static function email(object $settings, ?object $data = null): Response
    {
        return Controllers\EmailController::dispatch($settings, $data);
    }

    /**
     * This will send a web push notification.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    public static function push(object $settings, ?object $data = null): Response
    {
        return Controllers\WebPushController::dispatch($settings, $data);
    }
}