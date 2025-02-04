<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Controllers;

/**
 * Enqueuer
 *
 * This will enqueue messages by email, sms, and
 * push notification.
 *
 * @package Proto\Dispatch
 */
class Enqueuer
{
    /**
     * This will setup an sms message to enqueue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function sms(object $settings, ?object $data = null): object
    {
        return Controllers\TextController::enqueue($settings, $data);
    }

    /**
     * This will setup an email message to enqueue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function email(object $settings, ?object $data = null): object
    {
        return Controllers\EmailController::enqueue($settings, $data);
    }

    /**
     * This will setup a web push notification to enqueue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function push(object $settings, ?object $data = null): object
    {
        return Controllers\WebPushController::enqueue($settings, $data);
    }
}