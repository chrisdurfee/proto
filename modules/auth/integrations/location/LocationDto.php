<?php declare(strict_types=1);
namespace Modules\Auth\Integrations\Location;

/**
 * LocationDto
 *
 * Immutable value object representing an IP-based geo-lookup.
 *
 * @package Modules\Auth\Integrations\Location
 *
 * @property-read string|null $ip
 * @property-read string|null $network
 * @property-read string|null $version
 * @property-read string|null $city
 * @property-read string|null $region
 * @property-read string|null $regionCode
 * @property-read string|null $country
 * @property-read string|null $countryName
 * @property-read string|null $countryCode
 * @property-read string|null $countryCodeIso3
 * @property-read string|null $countryCapital
 * @property-read string|null $countryTld
 * @property-read string|null $continentCode
 * @property-read bool|null $inEu
 * @property-read string|null $postal
 * @property-read float|null $latitude
 * @property-read float|null $longitude
 * @property-read string|null $utcOffset
 * @property-read string|null $countryCallingCode
 * @property-read string|null $currency
 * @property-read string|null $currencyName
 * @property-read string|null $languages
 * @property-read float|null $countryArea
 * @property-read int|null $countryPopulation
 * @property-read string|null $asn
 * @property-read string|null $org
 * @property-read string|null $timezone
 * @property-read bool|null $success
 * @property-read string|null $position
 */
class LocationDto
{
	/**
	 * LocationDto constructor.
	 *
	 * @param string|null $ip
	 * @param string|null $network
	 * @param string|null $version
	 * @param string|null $city
	 * @param string|null $region
	 * @param string|null $regionCode
	 * @param string|null $country
	 * @param string|null $countryName
	 * @param string|null $countryCode
	 * @param string|null $countryCodeIso3
	 * @param string|null $countryCapital
	 * @param string|null $countryTld
	 * @param string|null $continentCode
	 * @param bool|null $inEu
	 * @param string|null $postal
	 * @param float|null $latitude
	 * @param float|null $longitude
	 * @param string|null $utcOffset
	 * @param string|null $countryCallingCode
	 * @param string|null $currency
	 * @param string|null $currencyName
	 * @param string|null $languages
	 * @param float|null $countryArea
	 * @param int|null $countryPopulation
	 * @param string|null $asn
	 * @param string|null $org
	 * @param string|null $timezone
	 * @param bool|null $success
	 * @param string|null $position
	 */
	public function __construct(
		public readonly ?string $ip,
		public readonly ?string $network,
		public readonly ?string $version,
		public readonly ?string $city,
		public readonly ?string $region,
		public readonly ?string $regionCode,
		public readonly ?string $country,
		public readonly ?string $countryName,
		public readonly ?string $countryCode,
		public readonly ?string $countryCodeIso3,
		public readonly ?string $countryCapital,
		public readonly ?string $countryTld,
		public readonly ?string $continentCode,
		public readonly ?bool $inEu,
		public readonly ?string $postal,
		public readonly ?float $latitude,
		public readonly ?float $longitude,
		public readonly ?string $utcOffset,
		public readonly ?string $countryCallingCode,
		public readonly ?string $currency,
		public readonly ?string $currencyName,
		public readonly ?string $languages,
		public readonly ?float $countryArea,
		public readonly ?int $countryPopulation,
		public readonly ?string $asn,
		public readonly ?string $org,
		public readonly ?string $timezone,
		public readonly ?bool $success,
		public readonly ?string $position = null
	)
	{
	}

	/**
	 * Factory that maps the API response payload to a DTO.
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
			$data->ip ?? null,
			$data->network ?? null,
			$data->version ?? null,
			$data->city ?? null,
			$data->region ?? null,
			$data->region_code ?? null,
			$data->country ?? null,
			$data->country_name ?? null,
			$data->country_code ?? null,
			$data->country_code_iso3 ?? null,
			$data->country_capital ?? null,
			$data->country_tld ?? null,
			$data->continent_code ?? null,
			isset($data->in_eu) ? (bool) $data->in_eu : null,
			$data->postal ?? null,
			isset($data->latitude) ? (float) $data->latitude : null,
			isset($data->longitude) ? (float) $data->longitude : null,
			$data->utc_offset ?? null,
			$data->country_calling_code ?? null,
			$data->currency ?? null,
			$data->currency_name ?? null,
			$data->languages ?? null,
			isset($data->country_area) ? (float) $data->country_area : null,
			isset($data->country_population) ? (int) $data->country_population : null,
			$data->asn ?? null,
			$data->org ?? null,
			$data->timezone ?? null,
			isset($data->success) ? (bool) $data->success : null,
			isset($data->latitude, $data->longitude) ? $data->latitude . ' ' . $data->longitude : null
		);
	}
}
