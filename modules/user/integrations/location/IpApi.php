<?php declare(strict_types=1);
namespace Modules\User\Integrations\Location;

use Proto\Integrations\RestService;

/**
 * IpApi
 *
 * This class provides methods to get the location of an IP address using the IpApi service.
 *
 * @package Modules\User\Integrations\Location
 */
class IpApi extends RestService
{
	/**
	 * @var string $url
	 */
	protected string $url = "https://ipapi.co/";

	/**
	 * This will get the ip address location.
	 *
	 * @param string $ipAddress
	 * @return object
	 */
	public function getLocation(string $ipAddress): object
	{
		$url = $ipAddress . '/json';
		return $this->fetch('GET', $url);
	}
}