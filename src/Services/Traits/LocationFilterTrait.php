<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * LocationFilterTrait
 *
 * Simplifies building MySQL spatial proximity filters using
 * ST_Distance_Sphere. Appends ready-to-use filter conditions
 * to a Proto-style filter array so callers never need to write
 * raw SQL distance expressions.
 *
 * Requires a MySQL POINT column on the target table for spatial
 * calculations. The POINT column should store (longitude, latitude)
 * per MySQL convention.
 *
 * Usage in a service:
 * ```php
 * use Proto\Services\Traits\LocationFilterTrait;
 *
 * class VehicleService extends Service
 * {
 *     use LocationFilterTrait;
 *
 *     public function addLocationFilter(int $userId, array &$filter): void
 *     {
 *         $userLocation = UserLocationPreference::getBy(['userId' => $userId]);
 *         if (!$userLocation)
 *         {
 *             return;
 *         }
 *
 *         $this->filterByProximity($filter, [
 *             'latitude' => $userLocation->latitude,
 *             'longitude' => $userLocation->longitude,
 *             'radius' => $userLocation->radiusMiles ?? 50,
 *         ]);
 *     }
 * }
 * ```
 *
 * Subquery variant (match against a related table):
 * ```php
 * $this->filterByProximitySubquery($filter, [
 *     'latitude' => $lat,
 *     'longitude' => $lon,
 *     'radius' => 50,
 *     'table' => 'user_location_preferences',
 *     'joinColumn' => 'user_id',
 *     'parentColumn' => 'v.user_id',
 * ]);
 * ```
 *
 * @package Proto\Services\Traits
 */
trait LocationFilterTrait
{
	/**
	 * Default miles-to-meters conversion factor.
	 *
	 * @var float
	 */
	private float $milesToMeters = 1609.34;

	/**
	 * Default radius in miles when none is specified.
	 *
	 * @var int
	 */
	private int $defaultRadiusMiles = 50;

	/**
	 * Append a direct proximity filter on a POINT column in the
	 * queried table itself.
	 *
	 * Options:
	 *  - latitude  (float, required)
	 *  - longitude (float, required)
	 *  - radius    (int|float, miles, default 50)
	 *  - column    (string, the POINT column, default 'position')
	 *  - alias     (string, table alias prefix, default '' — no alias)
	 *  - unit      (string, 'miles'|'km', default 'miles')
	 *
	 * @param array<mixed> &$filter The Proto filter array to append to.
	 * @param array<string, mixed> $options Location options.
	 * @return void
	 */
	protected function filterByProximity(array &$filter, array $options): void
	{
		$lat = $this->extractFloat($options, 'latitude');
		$lon = $this->extractFloat($options, 'longitude');
		if ($lat === null || $lon === null)
		{
			return;
		}

		$radiusMeters = $this->resolveRadiusMeters($options);
		$column = $this->resolveColumn($options);

		$filter[] = [
			"ST_Distance_Sphere({$column}, POINT(?, ?)) <= ?",
			[$lon, $lat, $radiusMeters]
		];
	}

	/**
	 * Append a proximity filter using an EXISTS subquery against
	 * a related table that holds the POINT column.
	 *
	 * This is useful when the spatial data lives in a different
	 * table (e.g. user_location_preferences) and you need to
	 * match rows from the primary table via a foreign key.
	 *
	 * Options (in addition to those in filterByProximity):
	 *  - table        (string, required — the related table name)
	 *  - joinColumn   (string, required — FK column in the related table)
	 *  - parentColumn (string, required — matching column in the parent query)
	 *  - column       (string, POINT column in related table, default 'position')
	 *  - tableAlias   (string, alias for the subquery table, default 'loc')
	 *
	 * @param array<mixed> &$filter The Proto filter array to append to.
	 * @param array<string, mixed> $options Location and join options.
	 * @return void
	 */
	protected function filterByProximitySubquery(array &$filter, array $options): void
	{
		$lat = $this->extractFloat($options, 'latitude');
		$lon = $this->extractFloat($options, 'longitude');
		if ($lat === null || $lon === null)
		{
			return;
		}

		$table = $options['table'] ?? null;
		$joinColumn = $options['joinColumn'] ?? null;
		$parentColumn = $options['parentColumn'] ?? null;
		if (!$table || !$joinColumn || !$parentColumn)
		{
			return;
		}

		$radiusMeters = $this->resolveRadiusMeters($options);
		$pointColumn = $options['column'] ?? 'position';
		$tableAlias = $options['tableAlias'] ?? 'loc';

		$filter[] = [
			"EXISTS (SELECT 1 FROM {$table} {$tableAlias} "
			. "WHERE {$tableAlias}.{$joinColumn} = {$parentColumn} "
			. "AND {$tableAlias}.{$pointColumn} IS NOT NULL "
			. "AND ST_Distance_Sphere({$tableAlias}.{$pointColumn}, POINT(?, ?)) <= ?)",
			[$lon, $lat, $radiusMeters]
		];
	}

	/**
	 * Build a standalone proximity filter condition array
	 * (not appended to an existing filter). Useful when you need
	 * the condition for further composition.
	 *
	 * @param array<string, mixed> $options Same options as filterByProximity.
	 * @return array{0: string, 1: array<mixed>}|null The filter condition or null if invalid.
	 */
	protected function buildProximityCondition(array $options): ?array
	{
		$lat = $this->extractFloat($options, 'latitude');
		$lon = $this->extractFloat($options, 'longitude');
		if ($lat === null || $lon === null)
		{
			return null;
		}

		$radiusMeters = $this->resolveRadiusMeters($options);
		$column = $this->resolveColumn($options);

		return [
			"ST_Distance_Sphere({$column}, POINT(?, ?)) <= ?",
			[$lon, $lat, $radiusMeters]
		];
	}

	/**
	 * Build a standalone subquery proximity condition.
	 *
	 * @param array<string, mixed> $options Same options as filterByProximitySubquery.
	 * @return array{0: string, 1: array<mixed>}|null The filter condition or null if invalid.
	 */
	protected function buildProximitySubqueryCondition(array $options): ?array
	{
		$filter = [];
		$this->filterByProximitySubquery($filter, $options);
		return $filter[0] ?? null;
	}

	/**
	 * Convert a radius value to meters.
	 *
	 * @param float|int $radius The radius value.
	 * @param string $unit The unit ('miles' or 'km').
	 * @return float The radius in meters.
	 */
	protected function convertToMeters(float|int $radius, string $unit = 'miles'): float
	{
		return match ($unit)
		{
			'km' => (float)$radius * 1000.0,
			default => (float)$radius * $this->milesToMeters,
		};
	}

	/**
	 * Resolve the radius in meters from the options array.
	 *
	 * @param array<string, mixed> $options
	 * @return float
	 */
	private function resolveRadiusMeters(array $options): float
	{
		$radius = (float)($options['radius'] ?? $this->defaultRadiusMiles);
		$unit = $options['unit'] ?? 'miles';
		return $this->convertToMeters($radius, $unit);
	}

	/**
	 * Resolve the fully qualified POINT column reference.
	 *
	 * @param array<string, mixed> $options
	 * @return string
	 */
	private function resolveColumn(array $options): string
	{
		$column = $options['column'] ?? 'position';
		$alias = $options['alias'] ?? '';
		return $alias ? "{$alias}.{$column}" : $column;
	}

	/**
	 * Extract and validate a float value from the options array.
	 *
	 * @param array<string, mixed> $options
	 * @param string $key
	 * @return float|null
	 */
	private function extractFloat(array $options, string $key): ?float
	{
		if (!isset($options[$key]) || $options[$key] === '' || $options[$key] === null)
		{
			return null;
		}
		return (float)$options[$key];
	}
}
