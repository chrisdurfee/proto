<?php declare(strict_types=1);
namespace Proto\Controllers\Sms;

use Proto\Integrations\Sms\TwilioTrustHub;
use Proto\Integrations\Sms\Twilio;
use Proto\Utils\Strings;

/**
 * TwilioController
 *
 * Creates a Twilio controller to work with the Twilio API.
 *
 * @package Proto\Controllers\Sms
 */
class TwilioController extends SmsController
{
	/**
	 * Sets up the Twilio integration.
	 *
	 * @param TwilioTrustHub $provisionApi
	 * @param Twilio $api
	 * @return void
	 */
	public function __construct(
		protected TwilioTrustHub $provisionApi = new TwilioTrustHub(),
		protected Twilio $api = new Twilio()
	)
	{
		parent::__construct();
	}

	/**
	 * Sends a message.
	 *
	 * @param string $session The client phone number.
	 * @param string $to The recipient phone number.
	 * @param string $message The message content.
	 * @return object|bool
	 */
	public function send(string $session, string $to, string $message): object|bool
	{
		$fromNumber = $session;
		$fromNumber = Strings::formatPhone($fromNumber);
		$to = Strings::formatPhone($to);

		$result = $this->api->send($fromNumber, $to, $message);
		return ($result->success == true) ? $result : false;
	}
}