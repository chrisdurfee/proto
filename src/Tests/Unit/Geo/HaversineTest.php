<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Geo;

use Proto\Geo\Haversine;
use PHPUnit\Framework\TestCase;

/**
 * HaversineTest
 *
 * @package Proto\Tests\Unit\Geo
 */
class HaversineTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testMilesIsZeroForSamePoint(): void
	{
		$this->assertSame(0.0, Haversine::miles(40.7128, -74.0060, 40.7128, -74.0060));
	}

	/**
	 * @return void
	 */
	public function testMilesMatchesKnownDistance(): void
	{
		// New York City to Los Angeles is ~2445 statute miles great-circle.
		$distance = Haversine::miles(40.7128, -74.0060, 34.0522, -118.2437);
		$this->assertGreaterThan(2400.0, $distance);
		$this->assertLessThan(2500.0, $distance);
	}

	/**
	 * @return void
	 */
	public function testKilometersIsProportionalToMiles(): void
	{
		$miles = Haversine::miles(40.7128, -74.0060, 34.0522, -118.2437);
		$km = Haversine::kilometers(40.7128, -74.0060, 34.0522, -118.2437);

		// 1 mile ~= 1.60934 km.
		$this->assertEqualsWithDelta($miles * 1.60934, $km, 1.0);
	}

	/**
	 * @return void
	 */
	public function testMilesIsSymmetric(): void
	{
		$a = Haversine::miles(40.7128, -74.0060, 34.0522, -118.2437);
		$b = Haversine::miles(34.0522, -118.2437, 40.7128, -74.0060);
		$this->assertEqualsWithDelta($a, $b, 0.0001);
	}

	/**
	 * @return void
	 */
	public function testMilesDoesNotProduceNanOnAntipodalPoints(): void
	{
		$distance = Haversine::miles(40.0, -105.0, -40.0, 75.0);
		$this->assertFalse(is_nan($distance));
	}

	/**
	 * @return void
	 */
	public function testAttachDistanceMilesSetsFieldOnRowsWithCoordinates(): void
	{
		$rows = [
			(object)['id' => 1, 'latitude' => 34.0522, 'longitude' => -118.2437],
			(object)['id' => 2, 'latitude' => null, 'longitude' => null],
		];

		Haversine::attachDistanceMiles($rows, ['latitude' => 40.7128, 'longitude' => -74.0060]);

		$this->assertIsFloat($rows[0]->distanceMiles);
		$this->assertGreaterThan(2400.0, $rows[0]->distanceMiles);
		$this->assertFalse(isset($rows[1]->distanceMiles));
	}

	/**
	 * @return void
	 */
	public function testAttachDistanceMilesSupportsCustomFieldNames(): void
	{
		$rows = [(object)['id' => 1, 'lat' => 34.0522, 'lng' => -118.2437]];

		Haversine::attachDistanceMiles(
			$rows,
			(object)['latitude' => 40.7128, 'longitude' => -74.0060],
			'lat',
			'lng',
			'milesAway',
			0
		);

		$this->assertIsFloat($rows[0]->milesAway);
		$this->assertSame(round($rows[0]->milesAway), $rows[0]->milesAway);
	}

	/**
	 * @return void
	 */
	public function testAttachDistanceMilesNoopsWithoutAnchorCoordinates(): void
	{
		$rows = [(object)['id' => 1, 'latitude' => 34.0522, 'longitude' => -118.2437]];
		Haversine::attachDistanceMiles($rows, []);

		$this->assertFalse(isset($rows[0]->distanceMiles));
	}

	/**
	 * @return void
	 */
	public function testAttachDistanceKilometersComposesWithPreferenceScorer(): void
	{
		$rows = [(object)['id' => 1, 'latitude' => 34.0522, 'longitude' => -118.2437]];
		Haversine::attachDistanceKilometers($rows, ['latitude' => 40.7128, 'longitude' => -74.0060]);

		$this->assertIsFloat($rows[0]->distanceKilometers);
		$this->assertGreaterThan(3800.0, $rows[0]->distanceKilometers);
	}
}
