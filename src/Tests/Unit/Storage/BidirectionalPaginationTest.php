<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Proto\Storage\Limit;
use Proto\Models\Model;

/**
 * Test bi-directional pagination (cursor and since)
 *
 * Note: These are simplified unit tests. For full integration testing,
 * see the feature tests with actual database queries.
 */
class BidirectionalPaginationTest extends TestCase
{
	/**
	 * Test getLastCursor helper returns last ID (legacy string signature)
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

	/**
	 * Create a mock model for compound cursor tests.
	 *
	 * @param string $idKeyName The ID key name.
	 * @param bool $isSnakeCase Whether the model uses snake_case.
	 * @param string $alias The table alias.
	 * @return Model
	 */
	protected function createMockModel(string $idKeyName = 'id', bool $isSnakeCase = true, string $alias = 'a'): Model
	{
		$model = $this->createMock(Model::class);
		$model->method('getIdKeyName')->willReturn($idKeyName);
		$model->method('isSnakeCase')->willReturn($isSnakeCase);
		$model->method('getAlias')->willReturn($alias);
		return $model;
	}

	/**
	 * Test getLastCursor with Model returns plain ID when no orderBy
	 */
	public function testGetLastCursorModelWithNoOrderBy(): void
	{
		$rows = [
			(object)['id' => 10],
			(object)['id' => 9],
			(object)['id' => 8],
		];

		$model = $this->createMockModel();
		$lastCursor = Limit::getLastCursor($rows, $model);

		$this->assertEquals(8, $lastCursor);
	}

	/**
	 * Test getLastCursor with Model returns plain ID when orderBy is ID only
	 */
	public function testGetLastCursorModelWithIdOnlyOrderBy(): void
	{
		$rows = [
			(object)['id' => 10],
			(object)['id' => 9],
		];

		$model = $this->createMockModel();
		$modifiers = ['orderBy' => ['id' => 'desc']];
		$lastCursor = Limit::getLastCursor($rows, $model, $modifiers);

		$this->assertEquals(9, $lastCursor);
	}

	/**
	 * Test getLastCursor with Model returns compound JSON cursor for multi-column orderBy
	 */
	public function testGetLastCursorModelWithMultiColumnOrderBy(): void
	{
		$rows = [
			(object)['id' => 5, 'next_service_date' => '2026-03-01', 'category' => 'oil'],
			(object)['id' => 3, 'next_service_date' => '2026-04-15', 'category' => 'brakes'],
		];

		$model = $this->createMockModel();
		$modifiers = ['orderBy' => ['nextServiceDate' => 'asc', 'category' => 'asc']];
		$lastCursor = Limit::getLastCursor($rows, $model, $modifiers);

		// Should return a JSON-encoded compound cursor
		$decoded = json_decode($lastCursor, true);
		$this->assertIsArray($decoded);
		$this->assertEquals('2026-04-15', $decoded['next_service_date']);
		$this->assertEquals('brakes', $decoded['category']);
		$this->assertEquals(3, $decoded['id']);
	}

	/**
	 * Test getLastCursor compound cursor includes ID tiebreaker
	 */
	public function testGetLastCursorCompoundIncludesId(): void
	{
		$rows = [
			(object)['id' => 7, 'status' => 'active'],
			(object)['id' => 4, 'status' => 'pending'],
		];

		$model = $this->createMockModel();
		$modifiers = ['orderBy' => ['status' => 'asc']];
		$lastCursor = Limit::getLastCursor($rows, $model, $modifiers);

		$decoded = json_decode($lastCursor, true);
		$this->assertIsArray($decoded);
		$this->assertArrayHasKey('id', $decoded);
		$this->assertEquals(4, $decoded['id']);
		$this->assertEquals('pending', $decoded['status']);
	}

	/**
	 * Test getLastCursor with empty rows and Model returns null
	 */
	public function testGetLastCursorModelWithEmptyRows(): void
	{
		$model = $this->createMockModel();
		$modifiers = ['orderBy' => ['nextServiceDate' => 'asc']];
		$lastCursor = Limit::getLastCursor([], $model, $modifiers);

		$this->assertNull($lastCursor);
	}

	/**
	 * Test compound cursor can be decoded back for use as next cursor
	 */
	public function testCompoundCursorRoundTrip(): void
	{
		$rows = [
			(object)['id' => 12, 'created_at' => '2026-01-01', 'priority' => 'high'],
			(object)['id' => 8, 'created_at' => '2026-02-15', 'priority' => 'low'],
		];

		$model = $this->createMockModel();
		$modifiers = ['orderBy' => ['createdAt' => 'desc', 'priority' => 'asc']];
		$lastCursor = Limit::getLastCursor($rows, $model, $modifiers);

		// Cursor is valid JSON
		$decoded = json_decode($lastCursor, true);
		$this->assertNotNull($decoded);

		// All orderBy columns and ID are present
		$this->assertArrayHasKey('created_at', $decoded);
		$this->assertArrayHasKey('priority', $decoded);
		$this->assertArrayHasKey('id', $decoded);
	}
}
