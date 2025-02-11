<?php declare(strict_types=1);
namespace Proto\Integrations\Sms;

use Proto\Integrations\Sms\Twilio;

/**
 * TwilioBundles
 *
 * This is used to interact with twilio bundles.
 *
 * @package Proto\Integrations\Sms
 */
class TwilioBundles extends Twilio
{
	/**
	 * This is the base url for the bundle api.
	 * This is used for each request.
     *
	 * @var string $url
	 */
	protected $url = "https://numbers.twilio.com/v2/RegulatoryCompliance/";

    /**
	 * This will delete a bundle.
	 *
	 * @param string $sid
	 * @return object
	 */
	public function deleteBundle(string $sid): object
	{
		$endPoint = "Bundles/{$sid}";

		return $this->fetch('DELETE', $endPoint, '', [], '204');
	}
}