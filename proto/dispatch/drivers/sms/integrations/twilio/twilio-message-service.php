<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\Sms\Twilio;
use Proto\Config;

/**
 * TwilioMessageService
 *
 * This is the class used to interact with the twilio messaging service api.
 * This api is used to create and manage messaging services for clients.
 *
 * Link to Docs: https://www.twilio.com/docs/messaging/services/api
 *
 * @package Proto\Integrations\Sms
 */
class TwilioMessageService extends Twilio
{
	/**
	 * This is a hard-coded value that doesn't change.
	 * This needs to be sent along with some requests such
	 * as the request to delete an A2P messaging campaign.
	 */
	final protected const COMPLIANCE_TYPE = 'QE2c6890da8086d771620e9b13fadeba0b';

	/**
	 * This is the base url for the messaging service api.
	 * This is used for each request.
	 * @var string $url
	 */
    protected $url = "https://messaging.twilio.com/v1/";

    /**
	 * This is the url that twilio will use
	 * to send a text message back to our server.
	 *
	 * @var string $messageHookUrl
	 */
	protected string $messageHookUrl;

	/**
	 * This will set the message hook url.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$settings = Config::getInstance();
		$this->messageHookUrl = $settings->sms->hooks->message;
	}

    /**
	 * This will create a new messaging service for a client.
	 *
	 * @param string $friendlyName
	 * @param string $customHookParams
	 * @return object
	 */
	public function createService(string $friendlyName, string $customHookParams = ''): object
	{
		$messageHook = $this->messageHookUrl . $customHookParams;
		$params = "FriendlyName={$friendlyName}" .
			      "&InboundRequestUrl={$messageHook}";

		return $this->fetch('POST', 'Services', $params, [], '201');
	}

	/**
	 * This will get a messaging service by sid.
	 *
	 * @param string $serviceSid
	 * @return object
	 */
	public function getService(string $serviceSid): object
	{
		$url = "Services/{$serviceSid}";

		return $this->fetch('GET', $url, '', [], '200');
	}

	/**
	 * This will get multiple messaging services with an optional limit.
	 *
	 * @param int|null $limit
	 * @return object
	 */
	public function getAllServices(?int $limit = null): object
	{
		$params = $limit ? "PageSize={$limit}" : '';
		return $this->fetch('GET', 'Services', $params, [], '200');
	}

	/**
	 * This will update a messaging service using a
	 * string of parameters and their updated values.
	 *
	 * Example parameters: "FriendlyName=NewName&InboundRequestUrl=NewUrl"
	 *
	 * @param string $serviceSid
	 * @param string $parameters
	 * @return object
	 */
	public function updateService(string $serviceSid, string $parameters): object
	{
		$url = "Services/{$serviceSid}";

		return $this->fetch('POST', $url, $parameters, [], '200');
	}

	/**
	 * This will delete a messaging service.
	 *
	 * Twilio will also delete the campaign
     * associated with the messaging service.
	 *
	 * @param string $serviceSid
	 * @return ?object
	 */
	public function deleteService(string $serviceSid): ?object
	{
		$url = "Services/{$serviceSid}";

		$result = $this->request('DELETE', $url, null, []);
		return $this->setupResponse('204', $result, '');
	}

	/**
	 * This will create an A2P brand for 10DLC compliance.
	 *
	 * Calls to this endpoint are a billable event.
	 *
	 * A2P Brand Registration can take up to several days in some cases.
     * If the brand is in the "IN_REVIEW" state for more than two days, it's
     * recommended to reach out to Twilio support.
	 *
	 * @link https://www.twilio.com/docs/sms/a2p-10dlc/onboarding-isv-api#3-create-an-a2p-brand
	 *
	 * @param string $clientProfileSid
	 * @param string $a2pProfileSid
	 * @return object
	 */
	public function createA2pBrand(
		string $clientProfileSid,
		string $a2pProfileSid,
		string $type = 'low-volume'
	): object
	{
		$endPoint = "a2p/BrandRegistrations";
		$params = "&CustomerProfileBundleSid={$clientProfileSid}" .
				  "&A2PProfileBundleSid={$a2pProfileSid}";

		if ($type === 'low-volume')
		{
			$params .= "&SkipAutomaticSecVet=True"; // This is the only parameter that needs to be added for low-volume brands.
		}

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
     * This will update the A2P brand registration for a client.
     * This can be used if an A2p brand needs to be updated
     * due to it having a status of FAILED.
	 *
	 * This needs to be called after updating the necessary information in the
	 * client profile or other objects.
     *
     * Newly updated information will usually need a
     * manual re-verification so it may take some time for the status
     * to be updated again.
     *
     * This can be called a maximum of 3 times at no
     * additional charge for a brand. Once the limit has been reached,
     * reach out to Twilio Support for help.
     *
     * @link https://www.twilio.com/docs/sms/a2p-10dlc/onboarding-isv-api#2-create-an-a2p-trust-product
     *
     * @param string $brandSid
     * @return object
     */
    public function updateA2pBrand(string $brandSid): object
	{
		$endPoint = "a2p/BrandRegistrations/{$brandSid}";

		return $this->fetch('POST', $endPoint, null, [], '200');
	}

	/**
     * This will get the A2P brand status.
     * Possible statuses are:
     * "PENDING", "APPROVED", "FAILED" or "IN_REVIEW.
     *
     * When the status is "IN_REVIEW" the brand is under manual
     * review and can take seven business days or more to complete.
     *
     * @link https://www.twilio.com/docs/sms/a2p-10dlc/onboarding-isv-api#301-using-fetch-to-check-brand-registration-status
     *
     * @param string $brandSid
     * @return object
     */
	public function getBrandStatus(string $brandSid): object
	{
		$endPoint = "a2p/BrandRegistrations/{$brandSid}";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will create a new use case for a service for 10DLC compliance.
	 *
	 * Params example:
	 * $params = "BrandRegistrationSid=$brandRegistrationSid" .
	 *		"&Description=Description of use" .
	 *		"&MessageSamples=MessageSamples" .
	 *		"&MessageSamples=MessageSamples" .
	 *		"&UsAppToPersonUsecase=STARTER" .
	 *		"&HasEmbeddedLinks=True" .
	 *		"&HasEmbeddedPhone=True" .
	 *		"&MessageFlow=message_flow";
	 *
	 * @param string $params
	 * @param string $serviceSid
	 * @return object
	 */
	public function createUseCase(string $params, string $serviceSid): object
	{
		$endPoint = "Services/{$serviceSid}/Compliance/Usa2p";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will create a new a2p campaign for a service for 10DLC compliance.
	 *
	 * Params example:
	 * $params = "BrandRegistrationSid=BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" .
     *             "&Description={$smsDescription}" .
     *             "&MessageSamples=Message Sample 1" .
     *             "&MessageSamples=Message Sample 2" .
     *             "&HasEmbeddedLinks=True" .
     *             "&HasEmbeddedPhone=True" .
     *             "&MessageFlow=End users opt-in by visiting www.acme.com, creating a new user account, consenting to receive marketing messages via text, and providing a valid mobile phone number." .
     *             "&UsAppToPersonUsecase=ACCOUNT_NOTIFICATION";
	 *
	 * @param string $params
	 * @param string $serviceSid
	 * @return object
	 */
	public function createCampaign(string $params, string $serviceSid): object
	{
		$endPoint = "Services/{$serviceSid}/Compliance/Usa2p";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	 /**
     * This will get the status of an A2P campaign.
	 *
	 * Possible statuses are:
	 * "IN_PROGRESS", "VERIFIED", "PENDING", or "FAILED".
	 *
	 * @link https://www.twilio.com/docs/sms/a2p-10dlc/onboarding-isv-api#53-check-your-campaign-registration-status
     *
     * @param string $messageServiceSid
     * @return object
     */
    public function getCampaignStatus(string $messageServiceSid): object
	{
		$endPoint = "Services/{$messageServiceSid}/Compliance/Usa2p/" . self::COMPLIANCE_TYPE;

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
     * This will delete an A2P messaging campaign.
     *
     * @param string $messageServiceSid
     * @return ?object
     */
    public function deleteCampaign(string $messageServiceSid): ?object
	{
		$endPoint = "Services/{$messageServiceSid}/Compliance/Usa2p/" . self::COMPLIANCE_TYPE;

		$result = $this->request('DELETE', $endPoint, null, []);
		return $this->setupResponse('204', $result, '');
	}

	/**
	 * This will add a phone number to a message service.
	 *
	 * @param string $numberSid
	 * @param string $serviceSid
	 * @return object
	 */
	public function addNumberToService(string $numberSid, string $serviceSid): object
	{
		$endPoint = "Services/{$serviceSid}/PhoneNumbers";
		$params = "PhoneNumberSid={$numberSid}";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}
}