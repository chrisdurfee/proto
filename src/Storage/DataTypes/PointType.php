<?php declare(strict_types=1);
namespace Proto\Storage\DataTypes;

/**
 * PointType
 *
 * Handles MySQL POINT(x, y) spatial data type.
 *
 * MySQL stores POINT as (X, Y) where X is longitude and Y is latitude.
 * Accept and emit values in lon-first order so they round-trip correctly
 * with ST_Distance_Sphere / MBR filters and WKB decoding.
 *
 * Supported input formats:
 * - String: `"lon lat"` (space-separated; same order as fromDb())
 * - Array: `[lon, lat]` or `['longitude' => lon, 'latitude' => lat]`
 * - Object: `{lon|lng|longitude|x, lat|latitude|y}`
 *
 * @package Proto\Storage\DataTypes
 */
class PointType extends DataType
{
	/**
	 * @inheritDoc
	 */
	public function getPlaceholder(): string
	{
		return 'POINT(?, ?)';
	}

	/**
	 * Converts a value to POINT(?, ?) bind params: [longitude, latitude].
	 *
	 * @inheritDoc
	 */
	public function toParams(mixed $value): array
	{
		if (empty($value))
		{
			return [null, null];
		}

		// Handle "lon lat" / "x y" format (matches fromDb output)
		if (is_string($value))
		{
			$parts = explode(' ', trim($value), 2);
			return [
				$parts[0] ?? null,
				$parts[1] ?? null
			];
		}

		// Handle array format [lon, lat] or named keys
		if (is_array($value))
		{
			if (isset($value['longitude']) || isset($value['lon']) || isset($value['lng']))
			{
				$lon = $value['longitude'] ?? $value['lon'] ?? $value['lng'] ?? null;
				$lat = $value['latitude'] ?? $value['lat'] ?? null;
				return [$lon, $lat];
			}

			return [
				$value[0] ?? null,
				$value[1] ?? null
			];
		}

		// Handle object format {lon|lng|longitude|x, lat|latitude|y}
		if (is_object($value))
		{
			$lon = $value->longitude ?? $value->lon ?? $value->lng ?? $value->x ?? null;
			$lat = $value->latitude ?? $value->lat ?? $value->y ?? null;
			return [$lon, $lat];
		}

		return [null, null];
	}

	/**
	 * Decodes a MySQL POINT binary (WKB with 4-byte SRID prefix) back to "lon lat" string.
	 *
	 * WKB X is longitude and Y is latitude; the returned string is `"x y"`
	 * (lon-first), matching toParams() string parsing.
	 *
	 * @inheritDoc
	 */
	public function fromDb(mixed $value): mixed
	{
		// MySQL POINT: 4-byte SRID + 1-byte byte-order + 4-byte WKB type + 8-byte X + 8-byte Y = 25 bytes
		if (!is_string($value) || strlen($value) !== 25)
		{
			return $value;
		}

		$byteOrder = ord($value[4]);
		$xBytes = substr($value, 9, 8);
		$yBytes = substr($value, 17, 8);

		if ($byteOrder === 0)
		{
			$xBytes = strrev($xBytes);
			$yBytes = strrev($yBytes);
		}

		$x = unpack('d', $xBytes)[1] ?? null;
		$y = unpack('d', $yBytes)[1] ?? null;

		if ($x === null || $y === null)
		{
			return $value;
		}

		return $x . ' ' . $y;
	}

	/**
	 * @inheritDoc
	 */
	public function shouldHandle(mixed $value): bool
	{
		return $value !== null && $value !== '';
	}
}
