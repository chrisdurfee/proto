<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Apns\Apns;
use Proto\Dispatch\Push\Template;
use Proto\Dispatch\Response;

/**
 * ApnsController
 *
 * Dispatches push notifications to native-shell installs via APNs.
 * Reuses the same push templates as web push: the compiled
 * `{ title, message, url }` JSON body is mapped onto Apple's `aps`
 * payload shape.
 *
 * @package Proto\Dispatch\Controllers
 */
class ApnsController extends Controller
{
	/**
	 * Compiles the push template.
	 *
	 * @param string $template The fully qualified push template class.
	 * @param object|null $data Optional data for the template.
	 * @return string
	 */
	protected static function createPush(string $template, ?object $data = null): string
	{
		return (string)Template::create($template, $data);
	}

	/**
	 * Maps a compiled web-push JSON body onto the APNs payload shape.
	 *
	 * @param string $template The compiled template JSON.
	 * @return string The APNs JSON payload.
	 */
	protected static function buildPayload(string $template): string
	{
		$body = json_decode($template) ?: (object)[];
		$alert = array_filter([
			'title' => $body->title ?? null,
			'body' => $body->message ?? null
		]);

		$payload = [
			'aps' => [
				'alert' => $alert ?: ['body' => 'You have a new notification.'],
				'sound' => 'default'
			]
		];

		if (!empty($body->url))
		{
			$payload['url'] = $body->url;
		}

		return (string)json_encode($payload);
	}

	/**
	 * Sets up an APNs dispatch to enqueue.
	 *
	 * Returns `{ tokens, message }` for app-owned queues. Proto does not
	 * ship an `apns_queue` table; apps that need durable enqueue should
	 * persist this payload themselves.
	 *
	 * @param object $settings The push settings.
	 * @param object|null $data Optional data for the push notification.
	 * @return object
	 */
	public static function enqueue(object $settings, ?object $data = null): object
	{
		$template = $settings->compiledTemplate ?? self::createPush($settings->template, $data);

		return (object)[
			'tokens' => $settings->tokens,
			'message' => self::buildPayload($template)
		];
	}

	/**
	 * Sends an APNs push notification.
	 *
	 * @param object $settings The push settings (`tokens`, `template`).
	 * @param object|null $data Optional data for the push notification.
	 * @return Response
	 */
	public static function dispatch(object $settings, ?object $data = null): Response
	{
		$template = $settings->compiledTemplate ?? self::createPush($settings->template, $data);
		return self::send(new Apns($settings->tokens, self::buildPayload($template)));
	}
}
