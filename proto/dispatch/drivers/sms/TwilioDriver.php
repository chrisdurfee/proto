<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Dispatch\Response;
use Proto\Controllers\Sms\TwilioController;

/**
 * Class TwilioDriver
 *
 * Sends a text message using the Twilio API.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
class TwilioDriver extends TextDriver
{
	/**
	 * Creates a new TwilioDriver object.
	 *
	 * @param TwilioController|null $controller The Twilio controller instance.
	 * @return void
	 */
	public function __construct(public ?TwilioController $controller = new TwilioController())
	{
	}

	/**
	 * Sends a text message.
	 *
	 * @param object $settings The text message settings.
	 * @return Response The response from sending the text message.
	 */
	public function send(object $settings): Response
	{
		if (empty($settings->session))
		{
			return $this->error('No client phone number found.'); // Enum message in messages table.
		}

		if (empty($settings->to))
		{
			return $this->error('No contact number setup.');
		}

		$result = $this->controller->send($settings->session, $settings->to, $settings->message);
		if (!$result)
		{
			return $this->error('The text failed to send.'); // Enum message in messages table.
		}

		return $this->response(false, 'Text message sent.', $result);
	}
}