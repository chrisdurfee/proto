<?php declare(strict_types=1);
namespace App\Dispatch;

use Proto\Dispatch\Enqueuer;
use App\Models\Queue\EmailQueue;
use App\Models\Queue\SmsQueue;
use App\Models\Queue\PushQueue;

/**
 * Enqueue
 *
 * This will enqueue a message.
 *
 * @package App\Dispatch
 */
class Enqueue
{
    /**
     * This will enqueue an sms message.
     *
     * @param object $settings
     * @param object|null $data
     * @return bool
     */
    public static function sms(object $settings, ?object $data = null): bool
    {
        $settings = Enqueuer::sms($settings, $data);

        $model = new SmsQueue($settings);
        return $model->add();
    }

    /**
     * This will enqueue an email.
     *
     * @param object $settings
     * @param object|null $data
     * @return bool
     */
    public static function email(object $settings, ?object $data = null): bool
    {
        $settings = Enqueuer::email($settings, $data);

        $model = new EmailQueue($settings);
        return $model->add();
    }

    /**
     * This will enqueue a push notification.
     *
     * @param object $settings
     * @param object|null $data
     * @return bool
     */
    public static function push(object $settings, ?object $data = null): bool
    {
        $settings = Enqueuer::push($settings, $data);

        $model = new PushQueue($settings);
        return $model->add();
    }
}