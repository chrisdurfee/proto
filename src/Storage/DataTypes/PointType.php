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
	 * @inheritDoc
	 */
	public function shouldHandle(mixed $value): bool
	{
		return $value !== null && $value !== '';
	}
}
