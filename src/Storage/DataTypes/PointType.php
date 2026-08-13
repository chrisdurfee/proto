<?php declare(strict_types=1);
namespace Proto\Storage\DataTypes;

/**
 * PointType
 *
 * Handles MySQL POINT(x, y) spatial data type.
 * Expects values in format "latitude longitude" (space-separated).
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
	 * @inheritDoc
	 */
	public function toParams(mixed $value): array
	{
		if (empty($value))
		{
			return [null, null];
		}

		// Handle "lat lon" format
		if (is_string($value))
		{
			$parts = explode(' ', $value, 2);
			return [
				$parts[0] ?? null,
				$parts[1] ?? null
			];
		}

		// Handle array format [lat, lon]
		if (is_array($value))
		{
			return [
				$value[0] ?? null,
				$value[1] ?? null
			];
		}

		// Handle object format {lat, lon} or {x, y}
		if (is_object($value))
		{
			return [
				$value->lat ?? $value->x ?? null,
				$value->lon ?? $value->y ?? null
			];
		}

		return [null, null];
	}

	/**
	 * Decodes a MySQL POINT binary (WKB with 4-byte SRID prefix) back to "x y" string.
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
