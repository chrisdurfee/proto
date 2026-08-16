<?php declare(strict_types=1);
namespace Proto\Geo;

/**
 * Haversine
 *
 * Great-circle distance helper for PHP-side proximity calculations.
 *
 * For SQL-side filtering prefer the MySQL `ST_Distance_Sphere(...)`
 * function (wrapped by `LocationFilterTrait` / `BoundingBox`). Use this
 * class for:
 *   - sorting/scoring an in-memory result set that already came from SQL
 *   - small batches where another round trip to MySQL is not worth it
 *   - feeding `PreferenceScorer`'s `distanceMiles` option
 *
 * @package Proto\Geo
 */
class Haversine
{
	/**
	 * IUGG mean Earth radius in statute miles.
	 */
	public const EARTH_RADIUS_MILES = 3958.7613;

	/**
	 * IUGG mean Earth radius in kilometres.
	 */
	public const EARTH_RADIUS_KM = 6371.0088;

	/**
	 * Great-circle distance between two coordinates in statute miles.
	 *
	 * @param float $lat1
	 * @param float $lng1
	 * @param float $lat2
	 * @param float $lng2
	 * @return float
	 */
	public static function miles(float $lat1, float $lng1, float $lat2, float $lng2): float
	{
		return self::compute($lat1, $lng1, $lat2, $lng2, self::EARTH_RADIUS_MILES);
	}

	/**
	 * Great-circle distance between two coordinates in kilometres.
	 *
	 * @param float $lat1
	 * @param float $lng1
	 * @param float $lat2
	 * @param float $lng2
	 * @return float
	 */
	public static function kilometers(float $lat1, float $lng1, float $lat2, float $lng2): float
	{
		return self::compute($lat1, $lng1, $lat2, $lng2, self::EARTH_RADIUS_KM);
	}

	/**
	 * Attach a computed distance field to every row that has lat/lng.
	 *
	 * Rows missing either coordinate field are left untouched (no
	 * distance field is set) so callers can distinguish "far away" from
	 * "no location on file".
	 *
	 * @param array<int, object> $rows
	 * @param array{latitude: float, longitude: float}|object $anchor
	 * @param string $latField Row property holding latitude (default 'latitude').
	 * @param string $lngField Row property holding longitude (default 'longitude').
	 * @param string $targetField Row property to set with the distance (default 'distanceMiles').
	 * @param int|null $round Decimal places to round to, or null to skip rounding.
	 * @return void
	 */
	public static function attachDistanceMiles(
		array &$rows,
		array|object $anchor,
		string $latField = 'latitude',
		string $lngField = 'longitude',
		string $targetField = 'distanceMiles',
		?int $round = 1
	): void
	{
		self::attachDistance($rows, $anchor, self::EARTH_RADIUS_MILES, $latField, $lngField, $targetField, $round);
	}

	/**
	 * Attach a computed distance field (kilometres) to every row that
	 * has lat/lng. Same semantics as {@see attachDistanceMiles()}.
	 *
	 * @param array<int, object> $rows
	 * @param array{latitude: float, longitude: float}|object $anchor
	 * @param string $latField
	 * @param string $lngField
	 * @param string $targetField
	 * @param int|null $round
	 * @return void
	 */
	public static function attachDistanceKilometers(
		array &$rows,
		array|object $anchor,
		string $latField = 'latitude',
		string $lngField = 'longitude',
		string $targetField = 'distanceKilometers',
		?int $round = 1
	): void
	{
		self::attachDistance($rows, $anchor, self::EARTH_RADIUS_KM, $latField, $lngField, $targetField, $round);
	}

	/**
	 * @param array<int, object> $rows
	 * @param array|object $anchor
	 * @param float $radius
	 * @param string $latField
	 * @param string $lngField
	 * @param string $targetField
	 * @param int|null $round
	 * @return void
	 */
	protected static function attachDistance(
		array &$rows,
		array|object $anchor,
		float $radius,
		string $latField,
		string $lngField,
		string $targetField,
		?int $round
	): void
	{
		$anchorArr = is_object($anchor) ? (array)$anchor : $anchor;
		if (!isset($anchorArr['latitude'], $anchorArr['longitude']))
		{
			return;
		}

		$anchorLat = (float)$anchorArr['latitude'];
		$anchorLng = (float)$anchorArr['longitude'];

		foreach ($rows as &$row)
		{
			if (!isset($row->$latField) || !isset($row->$lngField))
			{
				continue;
			}

			$distance = self::compute($anchorLat, $anchorLng, (float)$row->$latField, (float)$row->$lngField, $radius);
			$row->$targetField = ($round !== null) ? round($distance, $round) : $distance;
		}
		unset($row);
	}

	/**
	 * Shared computation; clamps the inside of `sqrt(1 - $a)` to avoid
	 * NaN on antipodal points where floating-point error pushes the
	 * value slightly above 1.
	 *
	 * @param float $lat1
	 * @param float $lng1
	 * @param float $lat2
	 * @param float $lng2
	 * @param float $radius
	 * @return float
	 */
	protected static function compute(float $lat1, float $lng1, float $lat2, float $lng2, float $radius): float
	{
		$dLat = deg2rad($lat2 - $lat1);
		$dLng = deg2rad($lng2 - $lng1);
		$a = sin($dLat / 2) ** 2
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
		$c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

		return $radius * $c;
	}
}
