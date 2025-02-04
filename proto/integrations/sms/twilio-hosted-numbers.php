<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\Sms\Twilio;
use Proto\Config;

/**
 * TwilioHostedNumbers
 *
 * This is the class used to interact with the twilio hosted numbers api.
 * This api is used to create and manage hosted numbers for clients.
 *
 * The hosted numbers api is currently under preview. Twilio is working on
 * this api and it is not fully functional yet.
 *
 * Docs: https://www.twilio.com/docs/phone-numbers/hosted-numbers/hosted-numbers-api/hosted-number-order-resource
 *
 * @package Proto\Integrations\Sms
 */
class TwilioHostedNumbers extends Twilio
{
    /**
     * This is the base url for the hosted numbers api.
     * This is used for each request.
     * @var string $url
     */
    protected $url = "https://preview.twilio.com/HostedNumbers/";

    /**
     * This is the status hook for a hosted number.
     *
     * @var string $numberStatusHook
     */
    protected string $numberStatusHook = '';

    /**
	 * This will set up the trust hub api.
     *
     * @return void
	 */
	public function __construct()
	{
		parent::__construct();

        $settings = Config::getInstance();
        $this->numberStatusHook = $settings->sms->hooks->numberStatus;
	}

    /**
     * This will create an order for a number to be hosted with Twilio.
     * See: https://www.twilio.com/docs/phone-numbers/hosted-numbers/hosted-numbers-api/hosted-number-order-resource#status-values for a list of statuses.
     *
     * This Twilio endpoint is still in Developer Preview.
     * "Currently, Twilio only has the ability to onboard landline or toll-free US & Canada numbers that are not currently SMS enabled."
     *
     * @param string $phoneNumber
     * @param string $friendlyName
     * @return object
     */
    public function createHostedNumberOrder(string $phoneNumber, string $friendlyName): object
    {
        $phoneNumber = urlencode($phoneNumber);
        $endPoint = "HostedNumberOrders";
        $params = "PhoneNumber={$phoneNumber}" .
                  "&SmsCapability=True" .
                  "&FriendlyName={$friendlyName}".
                  "&StatusCallbackUrl={$this->numberStatusHook}" .
                  "&StatusCallbackMethod=GET";

        return $this->fetch('POST', $endPoint, $params, [], '201');
    }

    /**
     * This will create an authorization document for a hosted number order.
     * It will be sent by twilio to the contact provided to sign.
     * See: https://www.twilio.com/docs/phone-numbers/hosted-numbers/hosted-numbers-api/authorization-document-resource for reference
     *
     * @param string $orderSid
     * @param string $addressSid
     * @param string $email
     * @param string $contactPhoneNumber
     * @param string $contactTitle
     * @return object
     */
    public function createAuthorizationDocument(
        string $orderSid,
        string $addressSid,
        string $email,
        string $contactPhoneNumber,
        string $contactTitle
    ): object
    {
        $contactPhoneNumber = urlencode($contactPhoneNumber);
        $endPoint = "AuthorizationDocuments";
        $params = "HostedNumberOrderSids={$orderSid}" .
                  "&AddressSid={$addressSid}" .
                  "&Email={$email}".
                  "&ContactPhoneNumber={$contactPhoneNumber}" .
                  "&ContactTitle={$contactTitle}";

        return $this->fetch('POST', $endPoint, $params, [], '201');
    }

    /**
     * This will get a hosted number order by sid. This can be used to check the status of an order.
     * See: https://www.twilio.com/docs/phone-numbers/hosted-numbers/hosted-numbers-api/hosted-number-order-resource for a list of statuses.
     *
     * @param string $orderSid
     * @return object
     */
    public function getHostedNumberOrder(string $orderSid): object
    {
        $endPoint = "HostedNumberOrders/{$orderSid}";

        return $this->fetch('GET', $endPoint, '', [], '200');
    }

    /**
     * This will get multiple hosted number orders with an optional limit.
     *
     * @param ?int $limit
     * @return object
     */
    public function getHostedNumberOrders($limit = null): object
    {
        $endPoint = "HostedNumberOrders";
        $params = $limit ? "PageSize={$limit}" : '';

        return $this->fetch('GET', $endPoint, $params, [], '200');
    }

    /**
     * This will update a hosted number order
     * with a string of parameters and their updated values.
     * Example parameters: "FriendlyName=NewName&SmsCapability=False".
     *
     * @param string $parameters
     * @param string $orderSid
     * @return object
     */
    public function updateHostedNumberOrder(string $parameters, string $orderSid): object
    {
        $endPoint = "HostedNumberOrders/{$orderSid}";

        return $this->fetch('POST', $endPoint, $parameters, [], '200');

    }

    /**
     * This will update a hosted number order status.
     * This is used to move an order to the next step in the process.
     *
     * @param string $parameters
     * @param string $orderSid
     * @param string $status
     * @return object
     */
    public function updateHostedNumberStatus(string $orderSid, string $status): object
    {
        $parameters = "Status={$status}";

        return $this->updateHostedNumberOrder($parameters, $orderSid);
    }

    /**
     * This will delete a hosted number order.
     * An order can only be deleted if it is in either the
     * received, pending-verification, verified, or pending-loa status.
     *
     * @param string $orderSid
     * @return object
     */
    public function deleteHostedNumberOrder(string $orderSid): object
    {
        $endPoint = "HostedNumberOrders/{$orderSid}";

        return $this->fetch('DELETE', $endPoint, '', [], '200');
    }
}