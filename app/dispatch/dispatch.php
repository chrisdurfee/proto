<?php declare(strict_types=1);
namespace App\Dispatch;

use Proto\Dispatch\Dispatcher;

/**
 * Dispatch
 *
 * This will dispatch messages.
 *
 * @package App\Dispatch
 */
class Dispatch
{
    /**
     * This will enqueue an sms message.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function sms(object $settings, ?object $data = null): object
    {
        return Dispatcher::sms($settings, $data);
    }

    /**
     * This will enqueue an email.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function email(object $settings, ?object $data = null): object
    {
        return Dispatcher::email($settings, $data);
    }

    /**
     * This will enqueue a push notification.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function push(object $settings, ?object $data = null): object
    {
        return Dispatcher::push($settings, $data);
    }
}