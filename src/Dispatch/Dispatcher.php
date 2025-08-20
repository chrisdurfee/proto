<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Controllers;

/**
 * Dispatcher
 *
 * This class dispatches messages via email, SMS,
 * and push notifications.
 *
 * @package Proto\Dispatch
 */
class Dispatcher
{
	/**
	 * Sends the provided dispatch.
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
	 * Creates a queued response.
	 *
	 * @param string $message
	 * @return Response
	 */
	protected static function createQueuedResponse(string $message): Response
	{
		$response = new Response(false, $message);
		$response->queue();
		return $response;
	}

	/**
	 * Sends an SMS message.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return Response
	 */
	public static function sms(object $settings, ?object $data = null): Response
	{
		if (isset($settings->queue))
		{
			Enqueuer::sms($settings, $data);
			return self::createQueuedResponse('SMS message queued.');
		}

		return Controllers\TextController::dispatch($settings, $data);
	}

	/**
	 * Sends an email.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return Response
	 */
	public static function email(object $settings, ?object $data = null): Response
	{
		if (isset($settings->queue))
		{
			Enqueuer::email($settings, $data);
			return self::createQueuedResponse('Email message queued.');
		}

		return Controllers\EmailController::dispatch($settings, $data);
	}

	/**
	 * Sends a web push notification.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return Response
	 */
	public static function push(object $settings, ?object $data = null): Response
	{
		if (isset($settings->queue))
		{
			Enqueuer::push($settings, $data);
			return self::createQueuedResponse('Web push message queued.');
		}

		return Controllers\WebPushController::dispatch($settings, $data);
	}
}