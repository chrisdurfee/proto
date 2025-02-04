<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\RestService;
use Proto\Config;

/**
 * Twilio
 *
 * This is the class used to interact with the primary twilio api.
 *
 * @package Proto\Integrations\Sms
 * @link https://www.twilio.com/docs/sms/api/message-resource
 */
class Twilio extends RestService
{
	/**
	 * This is the base url for the twilio api.
	 * This is used for each request.
	 *
	 * @var string $url
	 */
	protected $url = "https://api.twilio.com/2010-04-01/Accounts/";

	/**
	 * This is the api key for twilio.
	 *
	 * @var string $apiKey
	 */
	protected string $apiKey;

	/**
	 * This is the status callback url for twilio.
	 * This is used to send a callback to our server when a message is sent.
	 *
	 * This is set in the constructor.
	 *
	 * @var string $statusCallbackUrl
	 */
	protected string $statusCallbackUrl = '';

	/**
	 * This is our primary account session id.
	 * This is used to authenticate our account with the twilio api.
	 *
	 * @var string $sessionId
	 */
    protected string $sessionId;

	/**
	 * This will setup the twilio api.
	 *
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct();

		$this->apiKey = $this->getApiKey();
        $this->sessionId = $this->getSessionId();

		$this->setupUserCredentials($this->sessionId, $this->apiKey);
		$this->api->addCredentials = true;

		$settings = Config::getInstance();
		$this->statusCallbackUrl = $settings->sms->hooks->messageStatus;
	}

	/**
	 * This will send a text message with the twilio api.
	 *
	 * @param string $fromNumber
	 * @param string $toNumber
	 * @param string $message
	 * @return object|bool
	 */
	public function send(
		string $fromNumber,
		string $toNumber,
		string $message
	): object|bool
	{
		$this->encodeAmpersands($message);
		$endPoint = "{$this->sessionId}/Messages.json";
		$params = "Body={$message}" .
				  "&To={$toNumber}" .
				  "&From={$fromNumber}" .
				  "&StatusCallback={$this->statusCallbackUrl}";

		$response = $this->fetch('POST', $endPoint, $params, [], '201');
		if (isset($response->error_message) && !empty($response->error_message))
		{
			return false;
		}

		return $response;
	}

	/**
	 * Ampersands need to be encoded for twilio
	 * texts.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function encodeAmpersands(string &$message): void
	{
		$message = str_replace('&', '%26', $message);
	}

	/**
	 * This will get multiple phone numbers used with twilio.
	 *
	 * @param ?int $limit
	 * @param ?string $phone
	 * @return object
	 */
	public function getPhoneNumbers(?int $limit = null, ?string $phone = null): object
	{
		$endPoint = "{$this->sessionId}/IncomingPhoneNumbers.json";
		$params = $limit ? "PageSize={$limit}" : '';
		if ($phone)
		{
			$phone = urlencode($phone);
			$params .= "&PhoneNumber={$phone}";
		}

		return $this->fetch('GET', $endPoint, $params, [], '200');
	}

	/**
	 * This will get a phone number by sid.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function getPhoneNumber(string $phoneSid): object
	{
		$endPoint = "{$this->sessionId}/IncomingPhoneNumbers/{$phoneSid}.json";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will get multiple messages from twilio.
	 *
	 * @param ?int $limit
	 * @return object
	 */
	public function getMessages($limit = null): object
	{
		$endPoint = "{$this->sessionId}/Messages.json";
		$params = $limit ? "PageSize={$limit}" : '';

		return $this->fetch('GET', $endPoint, $params, [], '200');
	}

	/**
	 * This will get messages from twilio based on the given parameters.
	 *
	 * @param string $toNumber
	 * @param string $fromNumber
	 * @param string $date
	 * @param int $limit
	 * @return object
	 */
	public function findMessages(
		string $toNumber,
		string $fromNumber,
		string $date,
		int $limit
	): object
	{
		$endPoint = "{$this->sessionId}/Messages.json";
		$limitParam = $limit ? "&PageSize={$limit}" : '';
		$params = "To={$toNumber}" .
				  "&From={$fromNumber}" .
				  "&DateSent={$date}";
		$params .= $limitParam ? $limitParam : '';

		return $this->fetch('GET', $endPoint, $params, [], '200');
	}

	/**
	 * This will get messages from twilio based on the given parameters.
	 *
	 * @param string $toNumber
	 * @return object
	 */
	public function findMessagesForNumber(
		string $toNumber
	): object
	{
		$endPoint = "{$this->sessionId}/Messages.json";
		$params = "To={$toNumber}";

		return $this->fetch('GET', $endPoint, $params, [], '200');
	}

	/**
	 * This will create an address for a customer profile.
	 *
	 * @param object $client
	 * @param ?string $authorizedRepNumber
	 * @return object
	 */
	public function createCustomerProfileAddress(object $client, ?string $authorizedRepNumber = null): object
	{
		$client->country = $client->country ?? 'US';
		$name = $client->name ?? '';
		$name = urlencode($name);
		$street = urlencode($client->address);

		$endPoint = "{$this->sessionId}/Addresses.json";
		$params = "CustomerName={$name}" .
				  "&Street={$street}" .
				  "&City={$client->city}" .
				  "&Region={$client->state}" .
				  "&PostalCode={$client->zip}" .
				  "&IsoCountry={$client->country}" .
				  "&FriendlyName={$client->friendlyName}";
		if (isset($client->streetSecondary))
		{
			$streetSecondary = urlencode($client->streetSecondary);
			$params .= "&StreetSecondary={$streetSecondary}";
		}

		if (isset($authorizedRepNumber))
		{
			$authorizedRepNumber = urlencode($authorizedRepNumber);
			$params .= "&PhoneNumber={$authorizedRepNumber}";
		}

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will update an address for a customer profile.
	 *
	 * @param string $sid
	 * @param string $params
	 * @return object
	 */
	public function updateAddress(string $sid, string $params): object
	{
		$endPoint = "{$this->sessionId}/Addresses/{$sid}.json";

		return $this->fetch('POST', $endPoint, $params, [], '200');
	}

	/**
	 * This will get all addresses.
	 *
	 * @param string $sid
	 * @param string $params
	 * @return object
	 */
	public function getAllAddresses(): object
	{
		$endPoint = "{$this->sessionId}/Addresses.json?PageSize=200";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will get an address by sid.
	 *
	 * @param string $sid
	 * @return object
	 */
	public function getAddress(string $sid): object
	{
		$endPoint = "{$this->sessionId}/Addresses/{$sid}.json";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will get the api key.
	 *
	 * @return string
	 */
	protected function getApiKey(): string
	{
		if (isset($this->apiKey))
		{
			return $this->apiKey;
		}

		$settings = Config::getInstance();
		return $settings->sms->apiKey;
	}

    /**
     * This will get the sessionId.
     *
     * @return string
     */
    protected function getSessionId(): string
    {
        if (isset($this->sessionId))
        {
            return $this->sessionId;
        }

        $settings = Config::getInstance();
        return $settings->sms->sessionId;
    }
}