<?php declare(strict_types=1);
namespace Proto\Controllers\Sms;

use Proto\Integrations\Sms\TwilioTrustHub;
use Proto\Integrations\Sms\Twilio;
use Proto\Utils\Strings;

/**
 * TwilioController
 *
 * This will create a twilio controller to work with the
 * Twilio API.
 *
 * @package Proto\Controllers\Sms
 */
class TwilioController extends SmsController
{
	/**
	 * This will set up the twilio integration.
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
	 * This will send a message.
	 *
	 * @param string $session The client phone number
	 * @param string $to
	 * @param string $message
	 * @return object|bool
	 */
	public function send(
		string $session,
		string $to,
		string $message
	): object|bool
	{
		// let's convert the session to phoneNumber var for readability.
		$fromNumber = $session;

		/**
		 * This will ensure the phone numbers are in the E.164 format for Twilio.
		 */
		$fromNumber = Strings::formatPhone($fromNumber);
		$to = Strings::formatPhone($to);

		$result = $this->api->send($fromNumber, $to, $message);
		return ($result->success == true) ? $result : false;
	}
}