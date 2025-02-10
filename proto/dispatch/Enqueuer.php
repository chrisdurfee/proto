<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Controllers;

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
		return Controllers\TextController::enqueue($settings, $data);
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
		return Controllers\EmailController::enqueue($settings, $data);
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
		return Controllers\WebPushController::enqueue($settings, $data);
	}
}