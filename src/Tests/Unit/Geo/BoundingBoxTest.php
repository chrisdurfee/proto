<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Geo;

use Proto\Geo\BoundingBox;
use PHPUnit\Framework\TestCase;

/**
 * BoundingBoxTest
 *
 * @package Proto\Tests\Unit\Geo
 */
class BoundingBoxTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testFromRadiusMilesReturnsBox(): void
	{
		$box = BoundingBox::fromRadiusMiles(40.0, -105.0, 10.0);
		$this->assertNotNull($box);
		[$minLat, $maxLat, $minLon, $maxLon] = $box;
		$this->assertLessThan(40.0, $minLat);
		$this->assertGreaterThan(40.0, $maxLat);
		$this->assertLessThan(-105.0, $minLon);
		$this->assertGreaterThan(-105.0, $maxLon);
	}

	/**
	 * @return void
	 */
	public function testPolygonWktIsLngLatOrder(): void
	{
		$wkt = BoundingBox::polygonWktMiles(40.0, -105.0, 5.0);
		$this->assertNotNull($wkt);
		$this->assertStringStartsWith('POLYGON((', $wkt);
		$this->assertMatchesRegularExpression('/POLYGON\(\(-10[0-9.]+ 3[0-9.]+/', $wkt);
	}

	/**
	 * @return void
	 */
	public function testMbrConditionShape(): void
	{
		$condition = BoundingBox::mbrCondition('e.position', 40.0, -105.0, 25.0);
		$this->assertNotNull($condition);
		$this->assertStringContainsString('MBRContains', $condition[0]);
		$this->assertCount(1, $condition[1]);
	}

	/**
	 * @return void
	 */
	public function testZeroRadiusReturnsNull(): void
	{
		$this->assertNull(BoundingBox::fromRadiusMiles(40.0, -105.0, 0.0));
	}
}
