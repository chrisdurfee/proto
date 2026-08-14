<?php declare(strict_types=1);
namespace Proto\Geo;

/**
 * BoundingBox
 *
 * Derives a latitude/longitude bounding box (and its WKT polygon)
 * that fully encloses a circle of a given radius around a point.
 *
 * The box is used as an index-accelerated prefilter for spatial
 * proximity queries: `MBRContains(envelope, column)` can engage a
 * MySQL/MariaDB R-tree SPATIAL index, whereas `ST_Distance_Sphere`
 * alone forces a full table scan. The exact circle is then applied
 * with `ST_Distance_Sphere` over the (small) prefiltered set.
 *
 * The box is always a SUPERSET of the circle, so the prefilter
 * never drops a row the distance check would have kept.
 *
 * POINT columns store `(longitude, latitude)` per SQL convention,
 * so the emitted WKT is in `"lng lat"` (X Y) order to match.
 *
 * @package Proto\Geo
 */
class BoundingBox
{
	/**
	 * Approximate miles per degree of latitude (constant everywhere).
	 */
	private const MILES_PER_DEGREE_LAT = 69.0;

	/**
	 * Below this cosine the meridians converge too tightly (near the
	 * poles) for a stable longitude delta — fall back to distance-only.
	 */
	private const MIN_COS_LATITUDE = 0.000001;

	/**
	 * Compute the bounding box for a radius (miles) around a point.
	 *
	 * Returns `[minLat, maxLat, minLon, maxLon]`, or null when a safe
	 * box cannot be derived (non-positive radius, near-pole latitude,
	 * or a box that would wrap past the lat/lng limits). A null result
	 * signals the caller to rely on the distance refinement alone.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param float $radiusMiles
	 * @return array{0: float, 1: float, 2: float, 3: float}|null
	 */
	public static function fromRadiusMiles(float $latitude, float $longitude, float $radiusMiles): ?array
	{
		if ($radiusMiles <= 0.0)
		{
			return null;
		}

		$cosLat = cos(deg2rad($latitude));
		if (abs($cosLat) < self::MIN_COS_LATITUDE)
		{
			return null;
		}

		$deltaLat = $radiusMiles / self::MILES_PER_DEGREE_LAT;
		$deltaLon = $radiusMiles / (self::MILES_PER_DEGREE_LAT * abs($cosLat));

		$minLat = $latitude - $deltaLat;
		$maxLat = $latitude + $deltaLat;
		$minLon = $longitude - $deltaLon;
		$maxLon = $longitude + $deltaLon;

		// Box overflows the coordinate domain (poles / antimeridian) —
		// MBRContains cannot represent a wrapped envelope, so skip it.
		if ($minLat < -90.0 || $maxLat > 90.0 || $minLon < -180.0 || $maxLon > 180.0)
		{
			return null;
		}

		return [$minLat, $maxLat, $minLon, $maxLon];
	}

	/**
	 * Build a closed POLYGON WKT (in `"lng lat"` order) enclosing the
	 * radius, suitable for `ST_GeomFromText(?, 0)`. Returns null when a
	 * safe box cannot be derived.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param float $radiusMiles
	 * @return string|null
	 */
	public static function polygonWktMiles(float $latitude, float $longitude, float $radiusMiles): ?string
	{
		$box = self::fromRadiusMiles($latitude, $longitude, $radiusMiles);
		if ($box === null)
		{
			return null;
		}

		[$minLat, $maxLat, $minLon, $maxLon] = $box;

		// `%F` is locale-independent (always a dot decimal separator).
		return sprintf(
			'POLYGON((%F %F, %F %F, %F %F, %F %F, %F %F))',
			$minLon, $minLat,
			$maxLon, $minLat,
			$maxLon, $maxLat,
			$minLon, $maxLat,
			$minLon, $minLat
		);
	}

	/**
	 * Build the index-accelerated MBR prefilter condition for a POINT
	 * column, in Proto filter-array form: `[sql, [wkt]]`.
	 *
	 * Returns null when a safe bounding box cannot be derived; callers
	 * should then rely on the `ST_Distance_Sphere` refinement alone.
	 *
	 * @param string $column Fully-qualified POINT column (may be aliased).
	 * @param float $latitude
	 * @param float $longitude
	 * @param float $radiusMiles
	 * @return array{0: string, 1: array{0: string}}|null
	 */
	public static function mbrCondition(string $column, float $latitude, float $longitude, float $radiusMiles): ?array
	{
		$wkt = self::polygonWktMiles($latitude, $longitude, $radiusMiles);
		if ($wkt === null)
		{
			return null;
		}

		return [
			"MBRContains(ST_GeomFromText(?, 0), {$column})",
			[$wkt]
		];
	}
}
