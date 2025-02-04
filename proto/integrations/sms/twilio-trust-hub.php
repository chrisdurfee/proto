<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\Sms\Twilio;
use Proto\Config;
use Proto\Utils\Format\JsonFormat;

/**
 * TwilioTrustHub
 *
 * This is the class used to interact with the twilio trust hub api.
 * This api is used to migrate phone numbers over to twilio and
 * create 10DLC compliant profiles for clients.
 *
 * Docs: https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
 *
 * A2P 10DLC refers to a system in the United States
 * that allows businesses to send Application-to-Person (A2P)
 * type messaging via standard 10-digit long code (10DLC) phone numbers.
 * Carriers in the US consider all Twilio traffic to be A2P.
 *
 * @package Proto\Integrations\Sms
 */
class TwilioTrustHub extends Twilio
{
	/**
	 * This is the base url for the trust hub api.
	 * This is used for each request.
	 * @var string $url
	 */
	protected $url = "https://trusthub.twilio.com/v1/";

	/**
	 * This is the sid for the primary account.
	 * @var string $trustHubPrimaryAcountSid
	 */
	protected string $trustHubPrimaryAcountSid = '';

	/**
	 * This is the status hook for an a2p profile.
	 *
	 * @var string $a2pStatusHook
	 */
	protected string $a2pStatusHook = '';

	/**
	 * This is the status hook for a client profile.
	 *
	 * @var string $profileStatusHook
	 */
	protected string $profileStatusHook = '';

	/**
	 * This is the policy sid for a Secondary Customer Profile.
	 * This doesn't change.
	 *
	 * @var string
	 */
	final protected const SECONDARY_CUSTOMER_POLICY_ID = "RNdfbf3fae0e1107f8aded0e7cead80bf5";

	/**
	 * This is the sid for the a2p policy.
	 * This doesn't change.
	 *
	 * @var string
	 */
	final protected const A2P_POLICY_ID = 'RNb0d4771c2c98518d916a3d4cd70a8f8b';

	/**
	 * This will set up the trust hub api.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setupHooks();
	}

	/**
	 * This will set up the hooks needed for this api.
	 *
	 * @return void
	 */
	protected function setupHooks(): void
	{
		$settings = Config::getInstance();
		$this->trustHubPrimaryAcountSid = $settings->sms->trustHubPrimarySid;
		$this->a2pStatusHook = $settings->sms->hooks->a2pStatus;
		$this->profileStatusHook = $settings->sms->hooks->profileStatus;
	}

	/**
	 * This will create an end user object.
	 *
	 * @param object $attributes
	 * @param string $friendlyName
	 * @param string $type
	 * @return object
	 */
	public function createEndUserObject(
		object $attributes,
		string $friendlyName,
		string $type
	): object
	{
		$endPoint = "EndUsers";

		$attributes = JsonFormat::encode($attributes);
		$params = "Attributes={$attributes}" .
			      "&FriendlyName={$friendlyName}" .
			      "&Type={$type}";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will create an end user object.
	 *
	 * @param string $sid
	 * @return object
	 */
	public function getEndUser(
		string $sid
	): object
	{
		$endPoint = "EndUsers/{$sid}";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will create an end-user object business information for 10DLC compliance.
	 *
	 * @param object $business
	 * @param string $friendlyName
	 * @return object
	 */
	public function createEndUserBusinessInfo(object $business, string $friendlyName): object
	{
		$businessName = urlencode($business->name);
		$attributes = (object)[
			"business_identity" => "direct_customer",
			"business_industry" => $business->industry,
			"business_name" => $businessName,
			"business_regions_of_operation" => $business->regionsOfOperation ?? "USA_AND_CANADA",
			"business_registration_identifier" => $business->registrationIdentifier ?? "EIN",
			"business_registration_number" => $business->registrationNumber,
			"business_type" => $business->businessType ?? "Corporation",
			"social_media_profile_urls" => $business->socialMediaUrls ?? '',
			"website_url" => $business->websiteUrl
		];
		$type = "customer_profile_business_information";

		return $this->createEndUserObject($attributes, $friendlyName, $type);
	}

	/**
	 * This will create an end-user object authorized representative for 10DLC compliance.
	 * This person may be contacted for verification purposes in the future.
	 *
	 * @param object $authorizedRep
	 * @param string $friendlyName
	 * @return object
	 */
	public function createEndUserAuthorizedRep(object $authorizedRep, string $friendlyName): object
	{
		$email = $this->getSingleValue($authorizedRep->email);
		$phoneNumber = urlencode($authorizedRep->phone);
		$attributes = (object)[
			"business_title" => $authorizedRep->title,
			"first_name" => $authorizedRep->firstName,
			"last_name" => $authorizedRep->lastName,
			"email" => $email,
			"phone_number" => $phoneNumber,
			"job_position" => $authorizedRep->jobPosition
		];
		$type = "authorized_representative_1";

		return $this->createEndUserObject($attributes, $friendlyName, $type);
	}

	/**
	 * This will create a company end user object.
	 *
	 * @param object $business
	 * @param string $companyType
	 * @return object
	 */
	public function createCompanyType(object $business, string $companyType): object
	{
		if ($companyType === "public")
		{
			return $this->createPublicCompanyType($business);
		}

		$attributes = (object)[
			"company_type" => $companyType,
		];
		$friendlyName = "A2P for " . $business->friendlyName;
		$objectType = "us_a2p_messaging_profile_information";

		return $this->createEndUserObject($attributes, $friendlyName, $objectType);
	}

	/**
     * This will create an end user object for a company that's public.
     *
     * @return object
     */
	public function createPublicCompanyType(object $business): object
	{
		$attributes = (object)[
			"company_type" => "public",
			"stock_exchange" => $business->stockExchange,
			"stock_ticker" => $business->stockTicker,
		];
		$friendlyName = "A2P for " . $business->friendlyName;
		$type = "us_a2p_messaging_profile_information";

		return $this->createEndUserObject($attributes, $friendlyName, $type);
	}

	/**
	 * This will create the customer document for 10DLC compliance.
	 *
	 * @param object $client
	 * @param string $addressSid
	 * @return object
	 */
	public function createCustomerDocument(object $client, string $addressSid): object
	{
		$endPoint = "SupportingDocuments";
		$attributes = (object) [
			"address_sids" => $addressSid
		];

		$friendlyName = $client->friendlyName ?? $client->id;
		$attributes = JsonFormat::encode($attributes);
		$params = "Attributes={$attributes}" .
		          "&FriendlyName={$friendlyName}" .
				  "&Type=customer_profile_address";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will get a customer document.
	 *
	 * @param string $sid
	 * @return object
	 */
	public function getCustomerDocument(string $sid): object
	{
		$endPoint = "SupportingDocuments/{$sid}";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will attach an object to a profile.
	 *
	 * @param string $profileSid
	 * @param string $endPoint
	 * @param string $objectSid
	 * @return object
	 */
	public function attachToProfile(string $profileSid, string $endPoint, string $objectSid): object
	{
		$endPoint = "{$endPoint}/{$profileSid}/EntityAssignments";
		$params = "ObjectSid={$objectSid}";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will attach an object to a client profile.
	 *
	 * @param string $profileSid
	 * @param string $objectSid
	 * @return object
	 */
	public function attachToClientProfile(string $profileSid, string $objectSid): object
	{
		return $this->attachToProfile($profileSid, 'CustomerProfiles', $objectSid);
	}

	/**
	 * This will evaluate a profile for 10DLC compliance.
	 *
	 * @param string $profileSid
	 * @param string $endPoint
	 * @param string $policySid
	 * @return object
	 */
	public function evaluateProfile(
		string $profileSid,
		string $endPoint,
		string $policySid
	): object
	{
		$endPoint = "{$endPoint}/{$profileSid}/Evaluations";
		$params = "PolicySid={$policySid}";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will evaluate a client profile for 10DLC compliance.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function evaluateClientProfile(string $profileSid): object
	{
		return $this->evaluateProfile($profileSid, 'CustomerProfiles', self::SECONDARY_CUSTOMER_POLICY_ID);
	}

	/**
	 * This will submit a profile for review for 10DLC compliance.
	 *
	 * @param string $profileSid
	 * @param string $endPoint
	 * @return object
	 */
	public function submitForReview(
		string $profileSid,
		string $endPoint,
		string $statusCallback
	): object
	{
		$endPoint = "{$endPoint}/{$profileSid}";
		$params = "Status=pending-review" .
			      "&StatusCallback={$statusCallback}";

		return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
	 * This will submit a client profile for review for 10DLC compliance.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function submitClientProfileForReview(string $profileSid): object
	{
		return $this->submitForReview($profileSid, 'CustomerProfiles', $this->profileStatusHook);
	}

	/**
	 * This will create a profile for 10DLC compliance.
	 *
	 * @param object $client
	 * @param string $endPoint
	 * @param string $policySid
	 * @return object
	 */
	public function createProfile(
		object $client,
		string $endPoint,
		string $policySid,
		string $statusCallbackUrl
	): object
	{
		$friendlyName = $client->friendlyName ?? $client->id;
		$email = $this->getSingleValue($client->email);
		$endPoint = "{$endPoint}";
		$params = "FriendlyName={$friendlyName}" .
				  "&Email={$email}" .
				  "&PolicySid={$policySid}" .
				  "&StatusCallback={$statusCallbackUrl}";

        return $this->fetch('POST', $endPoint, $params, [], '201');
	}

	/**
     * This will return the string value or the first
     * value if there are multiple seperated by commas.
     *
     * @param string $value
     * @return string
     */
    private function getSingleValue(string $value): string
    {
        if (str_contains($value, ','))
        {
            $values = explode(',', $value);
            return $values[0];
        }

        return $value;
    }

	/**
	 * This will create a client profile for 10DLC compliance.
	 *
	 * @param object $client
	 * @return object
	 */
	public function createClientProfile(object $client): object
	{
		return $this->createProfile(
			$client,
			'CustomerProfiles',
			self::SECONDARY_CUSTOMER_POLICY_ID,
			$this->profileStatusHook
		);
	}

	/**
	 * This will create an a2p profile for 10DLC compliance
	 * for a client that sends less than 3,000 messages a day.
	 *
	 * @param object $client
	 * @param string $clientProfileSid
	 * @return object
	 */
	public function createA2pProfile(object $client): object
	{
		return $this->createProfile(
			$client,
			'TrustProducts',
			self::A2P_POLICY_ID,
			$this->a2pStatusHook
		);
	}

	/**
	 * This will attach an object to an A2P profile.
	 *
	 * @param string $profileSid
	 * @param string $objectSid
	 * @return object
	 */
	public function attachToA2pProfile(string $profileSid, string $objectSid): object
	{
		return $this->attachToProfile($profileSid, 'TrustProducts', $objectSid);
	}

	/**
	 * This will submit an A2P profile for review.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function submitA2pProfileForReview(string $profileSid): object
	{
		return $this->submitForReview($profileSid, 'TrustProducts', $this->a2pStatusHook);
	}

	/**
	 * This will evaluate an A2P profile for compliance.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function evaluateA2pProfile(string $profileSid): object
	{
		return $this->evaluateProfile($profileSid, 'TrustProducts', self::A2P_POLICY_ID);
	}

	/**
	 * This will get a customer profile by SID.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function getCustomerProfile(string $profileSid): object
	{
		$endPoint = "CustomerProfiles/{$profileSid}";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will get the entities assigned to
	 * a profile.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function getEntityAssignments(string $profileSid): object
	{
		$endPoint = "CustomerProfiles/{$profileSid}/EntityAssignments";

		return $this->fetch('GET', $endPoint, '', [], '200');

	}

	/**
	 * This will get the channel endpoint assignments for
	 * a profile.
	 *
	 * @param string $profileSid
	 * @return object
	 */
	public function getChannelEndpointAssignments(string $profileSid): object
	{
		$endPoint = "CustomerProfiles/{$profileSid}/ChannelEndpointAssignments";

		return $this->fetch('GET', $endPoint, '', [], '200');
	}

	/**
	 * This will delete a profile.
	 *
	 * @param string $sid
	 * @return object
	 */
	public function deleteProfile(string $sid): object
	{
		$endPoint = "CustomerProfiles/{$sid}";

		return $this->fetch('DELETE', $endPoint, '', [], '204');
	}
}