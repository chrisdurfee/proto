<?php declare(strict_types=1);

namespace Proto\Integrations\Sms;

use Proto\Integrations\RestService;
use Proto\Config;

/**
 * ZipwhipProvision
 *
 * This is the class used to interact with the zipwhip provision api.
 * This api is used to create and manage hosted numbers for clients.
 *
 * Docs: https://www.zipwhip.com/provisioning-api/
 *
 * @package Proto\Integrations\Sms
 */
class ZipwhipProvision extends RestService
{
	/**
	 * @var string $url
	 */
	protected $url = "https://provision.zipwhip.com/api/20140925/";

	/**
	 * @var string $apiKey
	 */
	protected $apiKey;

	/**
	 * Getting the api key from the .env file.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$settings = Config::getInstance();
		$this->apiKey = $settings->apis->zipwhip;
	}

	/**
	 * This will check a phone for eligibility.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function eligible(string $phoneNumber): object
	{
		$params = "api_key=" . $this->apiKey .
				  "&phone_number=" . $phoneNumber;

		$url = "provision/eligible";

		return $this->fetch('GET', $url, $params);
	}

	/**
	 * This will add a phone number.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function add(string $phoneNumber): object
	{
		$params = "api_key=" . $this->apiKey .
				  "&phone_number=+" . $phoneNumber;

		$url = "provision/add?api_key=" . $this->apiKey .
			   "&phone_number=+" . $phoneNumber .
			   "&account_name=Business Promotion" .
			   "&email=corey.blanchet@businesspromotion.com" .
			   "&feature_package=zipwhip_api";

		return $this->fetch('GET', $url, $params);
	}

	/**
	 * This will check the status of a phone number.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function status(string $phoneNumber): object
	{
		$params = "api_key=" . $this->apiKey .
				  "&phone_number=+" . $phoneNumber;

		$url = "provision/status?api_key=" . $this->apiKey .
			   "&phone_number=+" . $phoneNumber;

		return $this->fetch('GET', $url, $params);
	}

	/**
	 * This will update a phone number.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function update(string $phoneNumber): object
	{
		$params = "api_key=" . $this->apiKey .
				  "&phone_number=+" . $phoneNumber;

		$url = "provision/update?api_key=" . $this->apiKey .
			   "&phone_number=+" . $phoneNumber;

		return $this->fetch('GET', $url, $params);
	}

	/**
	 * This will delete a phone number.
	 *
	 * @param string $phoneNumber
	 * @return object
	 */
	public function delete(string $phoneNumber): object
	{
		$params = "api_key=" . $this->apiKey .
				  "&phone_number=+" . $phoneNumber;

		$url = "provision/delete?api_key=" . $this->apiKey .
			   "&phone_number=+" . $phoneNumber;

		return $this->fetch('GET', $url, $params);
	}
}