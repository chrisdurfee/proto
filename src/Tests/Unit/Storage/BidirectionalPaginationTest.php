<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Proto\Storage\Limit;

/**
 * Test bi-directional pagination (cursor and since)
 *
 * Note: These are simplified unit tests. For full integration testing,
 * see the feature tests with actual database queries.
 */
class BidirectionalPaginationTest extends TestCase
{
	/**
	 * Test getLastCursor helper returns last ID
	 */
	public function testGetLastCursor(): void
	{
		$rows = [
			(object)['id' => 100, 'text' => 'First'],
			(object)['id' => 99, 'text' => 'Second'],
			(object)['id' => 98, 'text' => 'Third'],
		];

		$lastCursor = Limit::getLastCursor($rows, 'id');

		// Should return the ID of the last row
		$this->assertEquals(98, $lastCursor);
	}

	/**
	 * Test getLastCursor with empty array returns null
	 */
	public function testGetLastCursorWithEmptyArray(): void
	{
		$lastCursor = Limit::getLastCursor([], 'id');
		$this->assertNull($lastCursor);
	}

	/**
	 * Test getLastCursor with different ID key name
	 */
	public function testGetLastCursorWithCustomIdKey(): void
	{
		$rows = [
			(object)['message_id' => 500, 'text' => 'First'],
			(object)['message_id' => 499, 'text' => 'Second'],
		];

		$lastCursor = Limit::getLastCursor($rows, 'message_id');
		$this->assertEquals(499, $lastCursor);
	}

	/**
	 * Test getLastCursor handles missing ID gracefully
	 */
	public function testGetLastCursorWithMissingId(): void
	{
		$rows = [
			(object)['id' => 100],
			(object)['text' => 'No ID here'],
		];

		$lastCursor = Limit::getLastCursor($rows, 'id');
		// Should return null since the last row has no 'id' property
		$this->assertNull($lastCursor);
	}
}
