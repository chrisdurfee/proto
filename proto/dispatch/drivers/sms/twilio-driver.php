<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Dispatch\Response;
use Proto\Controllers\Sms\TwilioController;

/**
 * TwilioDriver
 *
 * This will send a text message using the Twilio API.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
class TwilioDriver extends TextDriver
{
    /**
	 * This will create a new TwilioDriver object.
	 *
	 * @param TwilioController $controller
	 * @return void
	 */
    public function __construct(
		private TwilioController $controller = new TwilioController()
	)
    {
    }

    /**
	 * This will send a text message.
	 *
	 * @param object $settings
	 * @return Response
	 */
    public function send(object $settings): Response
	{
		if (empty($settings->session))
		{
			return $this->error('No client phone number found.'); // This is an enum value in the messages table.
		}

		if (empty($settings->to))
        {
            return $this->error('No contact number setup.');
        }

		$result = $this->controller->send($settings->session, $settings->to, $settings->message);
		if (!$result)
		{
			return $this->error('The text failed to send.'); // This is an enum value in the messages table.
		}

		return $this->response(false, 'Text message sent.', $result);
	}
}