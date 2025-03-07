<?php declare(strict_types=1);
namespace Common\Dispatch;

use Proto\Dispatch\Dispatcher;

/**
 * Class Dispatch
 *
 * Provides helper methods for dispatching various types of messages.
 *
 * @package Common\Dispatch
 */
class Dispatch
{
	/**
	 * Enqueues an SMS message.
	 *
	 * @param object $settings The SMS settings.
	 * @param object|null $data Optional additional data.
	 * @return object The dispatched SMS response.
	 */
	public static function sms(object $settings, ?object $data = null): object
	{
		return Dispatcher::sms($settings, $data);
	}

	/**
	 * Enqueues an email.
	 *
	 * @param object $settings The email settings.
	 * @param object|null $data Optional additional data.
	 * @return object The dispatched email response.
	 */
	public static function email(object $settings, ?object $data = null): object
	{
		return Dispatcher::email($settings, $data);
	}

	/**
	 * Enqueues a push notification.
	 *
	 * @param object $settings The push notification settings.
	 * @param object|null $data Optional additional data.
	 * @return object The dispatched push notification response.
	 */
	public static function push(object $settings, ?object $data = null): object
	{
		return Dispatcher::push($settings, $data);
	}
}
