<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Text\Template;
use Proto\Dispatch\Sms;
use Proto\Dispatch\Response;

/**
 * Class TextController
 *
 * This will be the base controller for all text dispatch controllers.
 *
 * @package Proto\Dispatch\Controllers
 */
class TextController extends Controller
{
	/**
	 * Creates a text template.
	 *
	 * @param string $template
	 * @param object|null $data
	 * @return string
	 */
	protected static function createText(string $template, ?object $data = null): string
	{
		return (string) Template::create($template, $data);
	}

	/**
	 * Sets up a text to queue.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return object
	 */
	public static function enqueue(object $settings, ?object $data = null): object
	{
		$message = $settings->compiledTemplate ?? static::createText($settings->template, $data);

		// The queue row has no subject column, so bake the prefix in now.
		if (!empty($settings->subject))
		{
			$message = $settings->subject . ' - ' . $message;
		}

		return (object)[
			'recipient' => $settings->to,
			'session' => $settings->session ?? null,
			'message' => (string) $message
		];
	}

	/**
	 * Sends a text.
	 *
	 * A precompiled body (`compiledTemplate`, which the queue routine
	 * sets from the stored row) always wins over re-rendering `template`.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return Response
	 */
	public static function dispatch(object $settings, ?object $data = null): Response
	{
		if (isset($settings->compiledTemplate))
		{
			$settings->message = $settings->compiledTemplate;
		}
		else if (isset($settings->template))
		{
			$settings->message = static::createText($settings->template, $data);
		}

		/**
		 * If the ssession is not set, we will use the default session
		 * from the env file.
		 */
		if (empty($settings->session))
		{
			$settings->session = env('sms')->session ?? '';
		}

		if (isset($settings->subject) && !empty($settings->subject))
		{
			$settings->message = $settings->subject . ' - ' . $settings->message;
		}

		// Custom driver can override the default driver
		$customDriver = $settings->customDriver ?? null;
		return static::send(new Sms($settings, $customDriver));
	}
}