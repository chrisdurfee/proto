<?php declare(strict_types=1);
namespace Modules\User\Integrations\Location;

use Proto\Integrations\RestService;

/**
 * IpApi
 *
 * Provides IP geolocation look‑ups via ipapi.co.
 *
 * @package Modules\User\Integrations\Location
 */
class IpApi extends RestService
{
	/**
	 * @var string $url
	 */
	protected string $url = 'https://ipapi.co/';

	/**
	 * Retrieves an IP address location.
	 *
	 * @param string $ipAddress
	 * @return LocationDto|null
	 */
	public function getLocation(string $ipAddress): ?LocationDto
	{
		$result = $this->fetch('GET', $ipAddress . '/json');
		return ($result && !isset($result->error)) ? LocationDto::create($result) : null;
	}
}