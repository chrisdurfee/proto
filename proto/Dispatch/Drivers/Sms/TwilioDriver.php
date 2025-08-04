<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Dispatch\Response;
use Twilio\Rest\Client;

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
	 * TwilioDriver constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
	}

	/**
	 * Validates the SMS settings.
	 *
	 * @param object $settings The text message settings.
	 * @return Response|null An error response if invalid, or null if valid.
	 */
	protected function validateSettings(object $settings): ?Response
	{
		if (empty($settings->to))
		{
			return $this->error('No contact number setup.');
		}
		if (empty($settings->message))
		{
			return $this->error('No message provided.');
		}
		return null;
	}

	/**
	 * Sends a text message.
	 *
	 * @param object $settings The text message settings. Requires:
	 *                         - to: Recipient phone number.
	 *                         - message: The SMS message body.
	 * @return Response The response from sending the text message.
	 */
	public function send(object $settings): Response
	{
		$validationError = $this->validateSettings($settings);
		if ($validationError !== null)
		{
			return $validationError;
		}

		$config = env('sms')->twilio ?? null;
		if (!$config)
		{
			return $this->error('Twilio configuration missing.');
		}

		$from = $config->from ?? '';
		$accountSid = $settings->session ?? $config->accountSid ?? '';
		$authToken = $config->authToken ?? '';
		if (empty($accountSid) || empty($authToken) || empty($from))
		{
			return $this->error('Incomplete Twilio configuration.');
		}

		try
		{
			$client = new Client($accountSid, $authToken);
			$message = $client->messages->create($settings->to, [
				'from' => $from,
				'body' => $settings->message,
			]);
			return $this->response(false, 'Text message sent.', $message);
		}
		catch (\Exception $e)
		{
			return $this->error('The text failed to send: ' . $e->getMessage());
		}
	}
}