<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Models\Queue\EmailQueue;
use Proto\Dispatch\Controllers;
use Proto\Dispatch\Models\Queue\SmsQueue;
use Proto\Dispatch\Models\Queue\PushQueue;
use Proto\Dispatch\Email\Unsubscribe\EmailHelper;

/**
 * Class Enqueuer
 *
 * Enqueues messages by email, SMS, and push notifications.
 *
 * @package Proto\Dispatch
 */
class Enqueuer
{
	/**
	 * Enqueues an SMS message.
	 *
	 * @param object $settings The SMS settings.
	 * @param object|null $data Additional SMS data.
	 * @return object The enqueued message object.
	 */
	public static function sms(object $settings, ?object $data = null): object
	{
		$settings = Controllers\TextController::enqueue($settings, $data);
		SmsQueue::create($settings);

		return $settings;
	}

	/**
	 * Enqueues an email message.
	 *
	 * @param object $settings The email settings.
	 * @param object|null $data Additional email data.
	 * @return object The enqueued message object.
	 */
	public static function email(object $settings, ?object $data = null): object
	{
		if (!isset($data->unsubscribeUrl))
		{
			$data->unsubscribeUrl = EmailHelper::createUnsubscribeUrl($data->to);
		}

		$settings = Controllers\EmailController::enqueue($settings, $data);
		EmailQueue::create($settings);

		return $settings;
	}

	/**
	 * Enqueues a web push notification.
	 *
	 * @param object $settings The web push settings.
	 * @param object|null $data Additional web push data.
	 * @return object The enqueued message object.
	 */
	public static function push(object $settings, ?object $data = null): object
	{
		$settings = Controllers\WebPushController::enqueue($settings, $data);
		PushQueue::create($settings);

		return $settings;
	}
}