<?php declare(strict_types=1);
namespace Proto\Services\Traits;

use Proto\Geo\BoundingBox;

/**
 * LocationFilterTrait
 *
 * Builds MySQL spatial proximity filters using an MBR bounding-box
 * prefilter (index-friendly) followed by `ST_Distance_Sphere` refinement.
 * The MBR is always a superset of the exact circle, so results match a
 * distance-only query while avoiding full table scans when a SPATIAL
 * index exists on the POINT column.
 *
 * Requires a MySQL POINT column storing (longitude, latitude).
 *
 * Usage:
 * ```php
 * $this->filterByProximity($filter, [
 *     'latitude' => $lat,
 *     'longitude' => $lon,
 *     'radius' => 50,
 * ]);
 * ```
 *
 * Optional `'mbr' => false` disables the bounding-box prefilter.
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
	 *  - mbr       (bool, default true — include MBR prefilter)
	 *
	 * @param array<mixed> &$filter The Proto filter array to append to.
	 * @param array<string, mixed> $options Location options.
	 * @return void
	 */
	protected function filterByProximity(array &$filter, array $options): void
	{
		$condition = $this->buildProximityCondition($options);
		if ($condition === null)
		{
			return;
		}

		foreach ($condition as $clause)
		{
			$filter[] = $clause;
		}
	}

	/**
	 * Append a proximity filter using an EXISTS subquery against
	 * a related table that holds the POINT column.
	 *
	 * Options (in addition to those in filterByProximity):
	 *  - table        (string, required — the related table name)
	 *  - joinColumn   (string, required — FK column in the related table)
	 *  - parentColumn (string, required — matching column in the parent query)
	 *  - column       (string, POINT column in related table, default 'position')
	 *  - tableAlias   (string, alias for the subquery table, default 'loc')
	 *  - mbr          (bool, default true)
	 *
	 * @param array<mixed> &$filter The Proto filter array to append to.
	 * @param array<string, mixed> $options Location and join options.
	 * @return void
	 */
	protected function filterByProximitySubquery(array &$filter, array $options): void
	{
		$condition = $this->buildProximitySubqueryCondition($options);
		if ($condition === null)
		{
			return;
		}

		$filter[] = $condition;
	}

	/**
	 * Build proximity filter condition(s) for a POINT column.
	 *
	 * Returns an array of one or two filter clauses (MBR prefilter when
	 * derivable, then exact distance), or null when coordinates are missing.
	 *
	 * Breaking note (1.3.50): previously returned a single `[sql, params]`
	 * clause. Callers that consumed the return value directly should iterate
	 * the array; `filterByProximity()` already does this.
	 *
	 * @param array<string, mixed> $options Same options as filterByProximity.
	 * @return array<int, array{0: string, 1: array<mixed>}>|null
	 */
	protected function buildProximityCondition(array $options): ?array
	{
		$lat = $this->extractFloat($options, 'latitude');
		$lon = $this->extractFloat($options, 'longitude');
		if ($lat === null || $lon === null)
		{
			return null;
		}

		$radiusMiles = $this->resolveRadiusMiles($options);
		$radiusMeters = $this->convertToMeters($radiusMiles);
		$column = $this->resolveColumn($options);

		$clauses = [];
		$useMbr = (bool)($options['mbr'] ?? true);
		if ($useMbr)
		{
			$mbr = BoundingBox::mbrCondition($column, $lat, $lon, $radiusMiles);
			if ($mbr !== null)
			{
				$clauses[] = $mbr;
			}
		}

		$clauses[] = [
			"ST_Distance_Sphere({$column}, POINT(?, ?)) <= ?",
			[$lon, $lat, $radiusMeters]
		];

		return $clauses;
	}

	/**
	 * Build a standalone subquery proximity condition with optional MBR.
	 *
	 * @param array<string, mixed> $options Same options as filterByProximitySubquery.
	 * @return array{0: string, 1: array<mixed>}|null
	 */
	protected function buildProximitySubqueryCondition(array $options): ?array
	{
		$lat = $this->extractFloat($options, 'latitude');
		$lon = $this->extractFloat($options, 'longitude');
		if ($lat === null || $lon === null)
		{
			return null;
		}

		$table = $options['table'] ?? null;
		$joinColumn = $options['joinColumn'] ?? null;
		$parentColumn = $options['parentColumn'] ?? null;
		if (!$table || !$joinColumn || !$parentColumn)
		{
			return null;
		}

		$radiusMiles = $this->resolveRadiusMiles($options);
		$radiusMeters = $this->convertToMeters($radiusMiles);
		$pointColumn = $options['column'] ?? 'position';
		$tableAlias = $options['tableAlias'] ?? 'loc';
		$aliasedColumn = "{$tableAlias}.{$pointColumn}";
		$useMbr = (bool)($options['mbr'] ?? true);

		$params = [];
		$mbrClause = '';
		if ($useMbr)
		{
			$wkt = BoundingBox::polygonWktMiles($lat, $lon, $radiusMiles);
			if ($wkt !== null)
			{
				$mbrClause = "AND MBRContains(ST_GeomFromText(?, 0), {$aliasedColumn}) ";
				$params[] = $wkt;
			}
		}

		$params[] = $lon;
		$params[] = $lat;
		$params[] = $radiusMeters;

		return [
			"EXISTS (SELECT 1 FROM {$table} {$tableAlias} "
			. "WHERE {$tableAlias}.{$joinColumn} = {$parentColumn} "
			. "AND {$aliasedColumn} IS NOT NULL "
			. $mbrClause
			. "AND ST_Distance_Sphere({$aliasedColumn}, POINT(?, ?)) <= ?)",
			$params
		];
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
	 * Resolve the radius in miles from the options array, normalizing
	 * the `km` unit so the bounding-box math stays in miles.
	 *
	 * @param array<string, mixed> $options
	 * @return float
	 */
	private function resolveRadiusMiles(array $options): float
	{
		$radius = (float)($options['radius'] ?? $this->defaultRadiusMiles);
		$unit = $options['unit'] ?? 'miles';
		if ($unit === 'km')
		{
			return ($radius * 1000.0) / $this->milesToMeters;
		}

		return $radius;
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
