<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

/**
 * AssertionHelpers
 *
 * Provides additional assertion utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait AssertionHelpers
{
	/**
	 * Asserts that a collection contains a specific item.
	 *
	 * @param mixed $item
	 * @param mixed $collection
	 * @return void
	 */
	protected function assertCollectionContains($item, $collection): void
	{
		if (is_array($collection))
		{
			$this->assertContains($item, $collection);
			return;
		}

		if (is_object($collection) && method_exists($collection, 'contains'))
		{
			$this->assertTrue($collection->contains($item),
				'Failed asserting that collection contains the specified item'
			);
			return;
		}

		if (is_object($collection) && method_exists($collection, 'toArray'))
		{
			$this->assertContains($item, $collection->toArray());
			return;
		}

		$this->fail('Collection type not supported for contains assertion');
	}

	/**
	 * Asserts that a collection has the expected count.
	 *
	 * @param int $count
	 * @param mixed $collection
	 * @return void
	 */
	protected function assertCollectionCount(int $count, $collection): void
	{
		if (is_array($collection))
		{
			$this->assertCount($count, $collection);
			return;
		}

		if (is_object($collection) && method_exists($collection, 'count'))
		{
			$this->assertEquals($count, $collection->count(),
				"Failed asserting that collection has {$count} items"
			);
			return;
		}

		if (is_countable($collection))
		{
			$this->assertCount($count, $collection);
			return;
		}

		$this->fail('Collection type not supported for count assertion');
	}

	/**
	 * Asserts that a collection is empty.
	 *
	 * @param mixed $collection
	 * @return void
	 */
	protected function assertCollectionEmpty($collection): void
	{
		$this->assertCollectionCount(0, $collection);
	}

	/**
	 * Asserts that a collection is not empty.
	 *
	 * @param mixed $collection
	 * @return void
	 */
	protected function assertCollectionNotEmpty($collection): void
	{
		if (is_array($collection))
		{
			$this->assertNotEmpty($collection);
			return;
		}

		if (is_object($collection) && method_exists($collection, 'count'))
		{
			$this->assertGreaterThan(0, $collection->count(),
				'Failed asserting that collection is not empty'
			);
			return;
		}

		if (is_countable($collection))
		{
			$this->assertGreaterThan(0, count($collection));
			return;
		}

		$this->fail('Collection type not supported for empty assertion');
	}

	/**
	 * Asserts that an array has keys.
	 *
	 * @param array $keys
	 * @param array $array
	 * @return void
	 */
	protected function assertArrayHasKeys(array $keys, array $array): void
	{
		foreach ($keys as $key)
		{
			$this->assertArrayHasKey($key, $array,
				"Failed asserting that array has key [{$key}]"
			);
		}
	}

	/**
	 * Asserts that an array does not have keys.
	 *
	 * @param array $keys
	 * @param array $array
	 * @return void
	 */
	protected function assertArrayMissingKeys(array $keys, array $array): void
	{
		foreach ($keys as $key)
		{
			$this->assertArrayNotHasKey($key, $array,
				"Failed asserting that array does not have key [{$key}]"
			);
		}
	}

	/**
	 * Asserts that a string matches a pattern.
	 *
	 * @param string $pattern
	 * @param string $string
	 * @return void
	 */
	protected function assertStringMatchesPattern(string $pattern, string $string): void
	{
		$this->assertMatchesRegularExpression($pattern, $string,
			"Failed asserting that string matches pattern [{$pattern}]"
		);
	}

	/**
	 * Asserts that a string contains all specified substrings.
	 *
	 * @param array $needles
	 * @param string $haystack
	 * @return void
	 */
	protected function assertStringContainsAll(array $needles, string $haystack): void
	{
		foreach ($needles as $needle) {
			$this->assertStringContainsString($needle, $haystack,
				"Failed asserting that string contains [{$needle}]"
			);
		}
	}

	/**
	 * Asserts that a value is between two values.
	 *
	 * @param mixed $min
	 * @param mixed $max
	 * @param mixed $actual
	 * @return void
	 */
	protected function assertBetween($min, $max, $actual): void
	{
		$this->assertGreaterThanOrEqual($min, $actual,
			"Failed asserting that {$actual} is greater than or equal to {$min}"
		);
		$this->assertLessThanOrEqual($max, $actual,
			"Failed asserting that {$actual} is less than or equal to {$max}"
		);
	}

	/**
	 * Asserts that a value is a valid email.
	 *
	 * @param string $email
	 * @return void
	 */
	protected function assertValidEmail(string $email): void
	{
		$this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
			"Failed asserting that [{$email}] is a valid email address"
		);
	}

	/**
	 * Asserts that a value is a valid URL.
	 *
	 * @param string $url
	 * @return void
	 */
	protected function assertValidUrl(string $url): void
	{
		$this->assertTrue(filter_var($url, FILTER_VALIDATE_URL) !== false,
			"Failed asserting that [{$url}] is a valid URL"
		);
	}

	/**
	 * Asserts that a timestamp is recent (within specified seconds).
	 *
	 * @param int $timestamp
	 * @param int $withinSeconds
	 * @return void
	 */
	protected function assertRecentTimestamp(int $timestamp, int $withinSeconds = 60): void
	{
		$now = time();
		$this->assertBetween($now - $withinSeconds, $now, $timestamp);
		$this->assertTrue(
			$timestamp >= ($now - $withinSeconds) && $timestamp <= $now,
			"Failed asserting that timestamp is within the last {$withinSeconds} seconds"
		);
	}

	/**
	 * Asserts that a date string is recent.
	 *
	 * @param string $dateString
	 * @param int $withinSeconds
	 * @return void
	 */
	protected function assertRecentDate(string $dateString, int $withinSeconds = 60): void
	{
		$timestamp = strtotime($dateString);
		$this->assertNotFalse($timestamp, "Failed to parse date string [{$dateString}]");
		$this->assertRecentTimestamp($timestamp, $withinSeconds);
	}
}