<?php declare(strict_types=1);

namespace Proto\Tests\Unit;

use Proto\Tests\Test;
use Proto\Tests\SimpleFaker;

/**
 * SimpleFakerTest
 *
 * Tests for the new SimpleFaker methods.
 */
class SimpleFakerTest extends Test
{
	protected SimpleFaker $faker;

	protected function setUp(): void
	{
		parent::setUp();
		$this->faker = new SimpleFaker();
	}

	public function testLatitudeGeneratesValidCoordinates(): void
	{
		$latitude = $this->faker->latitude();

		$this->assertIsFloat($latitude);
		$this->assertGreaterThanOrEqual(-90.0, $latitude);
		$this->assertLessThanOrEqual(90.0, $latitude);
	}

	public function testLatitudeRespectsCustomRange(): void
	{
		$latitude = $this->faker->latitude(25.0, 50.0);

		$this->assertGreaterThanOrEqual(25.0, $latitude);
		$this->assertLessThanOrEqual(50.0, $latitude);
	}

	public function testLongitudeGeneratesValidCoordinates(): void
	{
		$longitude = $this->faker->longitude();

		$this->assertIsFloat($longitude);
		$this->assertGreaterThanOrEqual(-180.0, $longitude);
		$this->assertLessThanOrEqual(180.0, $longitude);
	}

	public function testLongitudeRespectsCustomRange(): void
	{
		$longitude = $this->faker->longitude(-125.0, -65.0);

		$this->assertGreaterThanOrEqual(-125.0, $longitude);
		$this->assertLessThanOrEqual(-65.0, $longitude);
	}

	public function testRandomFloatWithFakerSignature(): void
	{
		$float = $this->faker->randomFloat(2, 10.0, 100.0);

		$this->assertIsFloat($float);
		$this->assertGreaterThanOrEqual(10.0, $float);
		$this->assertLessThanOrEqual(100.0, $float);

		// Check decimal places
		$decimalPart = explode('.', (string)$float)[1] ?? '';
		$this->assertLessThanOrEqual(2, strlen($decimalPart));
	}

	public function testDateTimeThisMonth(): void
	{
		$dateTime = $this->faker->dateTimeThisMonth();

		$this->assertIsString($dateTime);
		$timestamp = strtotime($dateTime);
		$this->assertNotFalse($timestamp);

		$oneMonthAgo = strtotime('-1 month');
		$now = time();

		$this->assertGreaterThanOrEqual($oneMonthAgo, $timestamp);
		$this->assertLessThanOrEqual($now, $timestamp);
	}

	public function testImageUrl(): void
	{
		$url = $this->faker->imageUrl();

		$this->assertIsString($url);
		$this->assertStringStartsWith('https://picsum.photos/', $url);
		$this->assertStringContainsString('640/480', $url);
	}

	public function testImageUrlWithCustomDimensions(): void
	{
		$url = $this->faker->imageUrl(800, 600);

		$this->assertStringContainsString('800/600', $url);
	}

	public function testImageUrlWithCategory(): void
	{
		$url = $this->faker->imageUrl(640, 480, 'nature');

		$this->assertStringContainsString('category=nature', $url);
	}

	public function testOptionalReturnsNullOrValue(): void
	{
		$results = [];

		// Run multiple times to check probability
		for ($i = 0; $i < 100; $i++)
		{
			$value = $this->faker->optional(0.5)->firstName();
			$results[] = $value;
		}

		$nullCount = count(array_filter($results, fn($v) => $v === null));
		$valueCount = count(array_filter($results, fn($v) => $v !== null));

		// Both should have some values (probabilistic test)
		$this->assertGreaterThan(0, $nullCount);
		$this->assertGreaterThan(0, $valueCount);
	}

	public function testOptionalWithHighProbability(): void
	{
		$results = [];

		// With 0.9 probability, most should be values
		for ($i = 0; $i < 100; $i++)
		{
			$value = $this->faker->optional(0.9)->firstName();
			$results[] = $value;
		}

		$valueCount = count(array_filter($results, fn($v) => $v !== null));

		// Should have mostly values (at least 70%)
		$this->assertGreaterThan(70, $valueCount);
	}

	public function testOptionalWithLowProbability(): void
	{
		$results = [];

		// With 0.1 probability, most should be null
		for ($i = 0; $i < 100; $i++)
		{
			$value = $this->faker->optional(0.1)->firstName();
			$results[] = $value;
		}

		$nullCount = count(array_filter($results, fn($v) => $v === null));

		// Should have mostly nulls (at least 70%)
		$this->assertGreaterThan(70, $nullCount);
	}
}
