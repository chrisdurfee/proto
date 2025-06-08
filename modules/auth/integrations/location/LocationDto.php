<?php declare(strict_types=1);
namespace Modules\Auth\Integrations\Location;

/**
 * LocationDto
 *
 * Immutable value object representing an IP‑based geo‑lookup.
 *
 * @package Modules\Auth\Integrations\Location
 */
class LocationDto
{
	/**
	 * LocationDto constructor.
	 *
	 * @param string|null $city
	 * @param string|null $region
	 * @param string|null $regionCode
	 * @param string|null $country
	 * @param string|null $countryCode
	 * @param string|null $postal
	 * @param float|null $latitude
	 * @param float|null $longitude
	 * @param string|null $timezone
	 */
	public function __construct(
		public readonly ?string $city,
		public readonly ?string $region,
		public readonly ?string $regionCode,
		public readonly ?string $country,
		public readonly ?string $countryCode,
		public readonly ?string $postal,
		public readonly ?float $latitude,
		public readonly ?float $longitude,
		public readonly ?string $position = null,
		public readonly ?string $timezone
	)
	{
	}

	/**
	 * Factory that maps the api response payload to a DTO.
	 *
	 * @param object $data
	 * @return self|null
	 */
	public static function create(object $data): ?self
	{
		if (isset($data->error))
		{
			return null;
		}

		return new self(
			$data->city ?? null,
			$data->region ?? null,
			$data->region_code ?? null,
			$data->country ?? null,
			$data->country_code ?? null,
			$data->postal ?? null,
			isset($data->latitude) ? (float)$data->latitude : null,
			isset($data->longitude) ? (float)$data->longitude : null,
			isset($data->latitude, $data->longitude) ? $data->latitude . ' ' . $data->longitude : null,
			$data->timezone ?? null
		);
	}
}