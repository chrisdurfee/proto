<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\Sms\Twilio;

/**
 * TwilioLookup
 *
 * This is the class used to interact with the twilio lookup api.
 *
 * Link to docs: https://www.twilio.com/docs/lookup/v2-api
 *
 * @package Proto\Integrations\Sms
 */
class TwilioLookup extends Twilio
{
    /**
     * This is the base url for the hosted numbers api.
     * This is used for each request.
     *
     * @var string $url
     */
    protected $url = "https://lookups.twilio.com/v2/PhoneNumbers/";

    /**
     * This will check the eligibility of a phone number to be hosted with Twilio.
     *
     * @param string $phoneNumber
     * @return object
     */
    public function checkEligibility(string $phoneNumber): object
    {
        $phoneNumber = urlencode($phoneNumber);
        $endPoint = "{$phoneNumber}";

        return $this->fetch('GET', $endPoint, '', [], '200');
    }
}