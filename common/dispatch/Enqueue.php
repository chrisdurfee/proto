<?php declare(strict_types=1);
namespace Common\Dispatch;

use Proto\Dispatch\Enqueuer;
use Common\Models\Queue\EmailQueue;
use Common\Models\Queue\SmsQueue;
use Common\Models\Queue\PushQueue;

/**
 * Class Enqueue
 *
 * Provides methods for enqueuing different types of messages (SMS, email, push notifications).
 *
 * @package Common\Dispatch
 */
class Enqueue
{
	/**
	 * Enqueues an SMS message.
	 *
	 * @param object $settings The SMS settings.
	 * @param object|null $data Optional additional data.
	 * @return bool Returns true if the SMS was successfully enqueued.
	 */
	public static function sms(object $settings, ?object $data = null): bool
	{
		$settings = Enqueuer::sms($settings, $data);
		$model = new SmsQueue($settings);
		return $model->add();
	}

	/**
	 * Enqueues an email.
	 *
	 * @param object $settings The email settings.
	 * @param object|null $data Optional additional data.
	 * @return bool Returns true if the email was successfully enqueued.
	 */
	public static function email(object $settings, ?object $data = null): bool
	{
		$settings = Enqueuer::email($settings, $data);
		$model = new EmailQueue($settings);
		return $model->add();
	}

	/**
	 * Enqueues a push notification.
	 *
	 * @param object $settings The push notification settings.
	 * @param object|null $data Optional additional data.
	 * @return bool Returns true if the push notification was successfully enqueued.
	 */
	public static function push(object $settings, ?object $data = null): bool
	{
		$settings = Enqueuer::push($settings, $data);
		$model = new PushQueue($settings);
		return $model->add();
	}
}