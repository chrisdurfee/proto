<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Services;

use Proto\Geo\BoundingBox;
use Proto\Services\Traits\LocationFilterTrait;
use PHPUnit\Framework\TestCase;

/**
 * LocationFilterTraitTest
 *
 * @package Proto\Tests\Unit\Services
 */
class LocationFilterTraitTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testFilterByProximityIncludesMbrAndDistance(): void
	{
		$service = new class {
			use LocationFilterTrait;

			public function run(array &$filter, array $options): void
			{
				$this->filterByProximity($filter, $options);
			}

			public function build(array $options): ?array
			{
				return $this->buildProximityCondition($options);
			}
		};

		$filter = [];
		$service->run($filter, [
			'latitude' => 40.0,
			'longitude' => -105.0,
			'radius' => 25,
			'alias' => 'e',
		]);

		$this->assertGreaterThanOrEqual(1, count($filter));
		$joined = implode(' ', array_map(fn($c) => $c[0], $filter));
		$this->assertStringContainsString('ST_Distance_Sphere', $joined);

		$mbr = BoundingBox::mbrCondition('e.position', 40.0, -105.0, 25.0);
		if ($mbr !== null)
		{
			$this->assertStringContainsString('MBRContains', $joined);
			$this->assertCount(2, $filter);
		}

		$withoutMbr = $service->build([
			'latitude' => 40.0,
			'longitude' => -105.0,
			'radius' => 25,
			'mbr' => false,
		]);
		$this->assertCount(1, $withoutMbr);
	}
}
