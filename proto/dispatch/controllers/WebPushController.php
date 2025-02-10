<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Push\Template;
use Proto\Dispatch\WebPush;
use Proto\Dispatch\Response;

/**
 * Class WebPushController
 *
 * This will be the controller for web push notifications.
 *
 * @package Proto\Dispatch\Controllers
 */
class WebPushController extends Controller
{
	/**
	 * Creates a push template.
	 *
	 * @param string $template The fully qualified class name for the push template.
	 * @param object|null $data Optional data for the push template.
	 * @return string
	 */
	protected static function createPush(string $template, ?object $data = null): string
	{
		return (string) Template::create($template, $data);
	}

	/**
	 * Sets up a push notification to queue.
	 *
	 * @param object $settings The push settings.
	 * @param object|null $data Optional data for the push notification.
	 * @return object
	 */
	public static function enqueue(object $settings, ?object $data = null): object
	{
		$template = self::createPush($settings->template, $data);

		return (object)[
			'subscriptions' => $settings->subscriptions,
			'message'       => (string)$template
		];
	}

	/**
	 * Sends a push notification.
	 *
	 * @param object $settings The push settings.
	 * @param object|null $data Optional data for the push notification.
	 * @return Response
	 */
	public static function dispatch(object $settings, ?object $data = null): Response
	{
		$template = $settings->compiledTemplate ?? self::createPush($settings->template, $data);
		return self::send(new WebPush($settings->subscriptions, $template));
	}
}