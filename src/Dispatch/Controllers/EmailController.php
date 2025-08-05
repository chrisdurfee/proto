<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Email\Template;
use Proto\Dispatch\Email;
use Proto\Dispatch\Email\Unsubscribe\EmailHelper;
use Proto\Dispatch\Response;

/**
 * Class EmailController
 *
 * This will be the controller for email dispatches.
 *
 * @package Proto\Dispatch\Controllers
 */
class EmailController extends Controller
{
	/**
	 * Creates an email template.
	 *
	 * @param string $template The fully qualified class name for the email template.
	 * @param object|null $data Optional data to pass to the email template.
	 * @return string
	 */
	protected static function createEmail(string $template, ?object $data = null): string
	{
		return (string) Template::create($template, $data);
	}

	/**
	 * Gets the email defaults.
	 *
	 * @param object $settings The email settings.
	 * @return object
	 */
	protected static function getEmailDefaults(object $settings): object
	{
		$email = env('email');

		return (object)[
			'to' => $settings->to,
			'from' => $settings->from ?? $email->default,
			'fromName' => $settings->fromName ?? $email->fromName,
			'subject' => $settings->subject,
			'unsubscribeUrl' => $settings->unsubscribeUrl ?? EmailHelper::createUnsubscribeUrl($settings->to),
			'attachments' => $settings->attachments ?? null
		];
	}

	/**
	 * Sets up an email to queue.
	 *
	 * @param object $settings The email settings.
	 * @param object|null $data Optional data for the email.
	 * @return object
	 */
	public static function enqueue(object $settings, ?object $data = null): object
	{
		$data = self::setEmailData($data, $settings);
		$template = self::createEmail($settings->template, $data);
		$email = env('email');

		return (object)[
			'recipient' => $settings->to,
			'from' => $settings->from ?? $email->default,
			'fromName' => $settings->fromName ?? $email->fromName,
			'subject' => $settings->subject,
			'message' => (string) $template,
			'unsubscribeUrl' => $settings->unsubscribeUrl ?? EmailHelper::createUnsubscribeUrl($settings->to),
			'attachments' => $settings->attachments ?? null
		];
	}

	/**
	 * Sets the public unsubscribe URL.
	 *
	 * @param ?string $email
	 * @return string
	 */
	protected static function setPublicUnsubscribeUrl(?string $email): string
	{
		$params = EmailHelper::getUnsubscribeUrlParams($email);
		if (!$params)
		{
			return '';
		}

		$baseUrl = "/email-unsubscribe";
		return 'https://' . envUrl() . $baseUrl . $params;
	}

	/**
	 * Sets the email data.
	 *
	 * @param object|null $data
	 * @param object $settings
	 * @return object
	 */
	protected static function setEmailData(?object &$data, object $settings): object
	{
		/**
		 * We want to add the public unsubscribe URL to the email data.
		 */
		$data = (is_object($data) ? $data : new \stdClass());
		$data->unsubscribeUrl = static::setPublicUnsubscribeUrl($settings->to);
		return $data;
	}

	/**
	 * Sends an email.
	 *
	 * @param object $settings The email settings.
	 * @param object|null $data Optional data for the email.
	 * @return Response
	 */
	public static function dispatch(object $settings, ?object $data = null): Response
	{
		$data = self::setEmailData($data, $settings);

		$template = $settings->compiledTemplate ?? self::createEmail($settings->template, $data);
		$settings = self::getEmailDefaults($settings);

		$attachments = !empty($settings->attachments) ? $settings->attachments : null;
		$email = new Email($settings->to, 'html', $settings->from, $settings->subject, $template, $attachments);

		if (!empty($settings->unsubscribeUrl))
		{
			$email->setUnsubscribeUrl($settings->unsubscribeUrl);
		}

		return self::send($email);
	}
}