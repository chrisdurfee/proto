<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Models\Data;

use PHPUnit\Framework\TestCase;
use Proto\Models\Data\NestedDataHelper;

/**
 * Test NestedDataHelper
 */
class NestedDataHelperTest extends TestCase
{
	/**
	 * Test that array of arrays is converted to array of objects
	 */
	public function testArrayOfArraysConvertedToObjects(): void
	{
		$helper = new NestedDataHelper();
		$helper->addKey('participants');

		// Simulate what comes from database - array of associative arrays
		$input = [
			[
				'id' => 1,
				'userId' => 123,
				'role' => 'admin',
				'displayName' => 'John Doe'
			],
			[
				'id' => 2,
				'userId' => 456,
				'role' => 'member',
				'displayName' => 'Jane Smith'
			]
		];

		$result = $helper->getGroupedData($input);

		// Should still be an array
		$this->assertIsArray($result);
		$this->assertCount(2, $result);

		// But each item should now be an object
		$this->assertIsObject($result[0]);
		$this->assertIsObject($result[1]);

		// And properties should be accessible
		$this->assertEquals(1, $result[0]->id);
		$this->assertEquals(123, $result[0]->userId);
		$this->assertEquals('admin', $result[0]->role);
		$this->assertEquals('John Doe', $result[0]->displayName);

		$this->assertEquals(2, $result[1]->id);
		$this->assertEquals(456, $result[1]->userId);
		$this->assertEquals('member', $result[1]->role);
		$this->assertEquals('Jane Smith', $result[1]->displayName);
	}

	/**
	 * Test that nested objects are properly converted
	 */
	public function testNestedObjectsConversion(): void
	{
		$helper = new NestedDataHelper();
		$helper->addKey('participants');

		$input = [
			[
				'id' => 1,
				'user' => [
					'id' => 123,
					'name' => 'John Doe',
					'email' => 'john@example.com'
				]
			],
			[
				'id' => 2,
				'user' => [
					'id' => 456,
					'name' => 'Jane Smith',
					'email' => 'jane@example.com'
				]
			]
		];

		$result = $helper->getGroupedData($input);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);

		// Each item should be an object
		$this->assertIsObject($result[0]);
		$this->assertIsObject($result[1]);

		// Nested user should also be an object
		$this->assertIsObject($result[0]->user);
		$this->assertEquals(123, $result[0]->user->id);
		$this->assertEquals('John Doe', $result[0]->user->name);

		$this->assertIsObject($result[1]->user);
		$this->assertEquals(456, $result[1]->user->id);
		$this->assertEquals('Jane Smith', $result[1]->user->name);
	}

	/**
	 * Test that JSON strings are still properly decoded
	 */
	public function testJsonStringDecoding(): void
	{
		$helper = new NestedDataHelper();
		$helper->addKey('participants');

		$jsonString = json_encode([
			['id' => 1, 'name' => 'John'],
			['id' => 2, 'name' => 'Jane']
		]);

		$result = $helper->getGroupedData($jsonString);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertIsObject($result[0]);
		$this->assertEquals(1, $result[0]->id);
		$this->assertEquals('John', $result[0]->name);
	}

	/**
	 * Test that numeric arrays are preserved
	 */
	public function testNumericArraysPreserved(): void
	{
		$helper = new NestedDataHelper();

		$input = [1, 2, 3, 4, 5];

		$result = $helper->getGroupedData($input);

		$this->assertIsArray($result);
		$this->assertEquals([1, 2, 3, 4, 5], $result);
	}

	/**
	 * Test that arrays with numeric string keys are converted to numeric arrays
	 * This handles the case where JSON like {"1":{...},"2":{...}} is decoded
	 */
	public function testNumericStringKeysConvertedToNumericArray(): void
	{
		$helper = new NestedDataHelper();
		$helper->addKey('participants');

		// Simulate what comes from JSON decode of {"1":{...},"2":{...}}
		$input = [
			"1" => [  // string key "1"
				'id' => 1,
				'userId' => 221,
				'role' => 'member',
				'displayName' => 'batman'
			],
			"2" => [  // string key "2"
				'id' => 2,
				'userId' => 456,
				'role' => 'admin',
				'displayName' => 'superman'
			]
		];

		$result = $helper->getGroupedData($input);

		// Should be a numeric array (re-indexed to 0, 1)
		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertArrayHasKey(0, $result);
		$this->assertArrayHasKey(1, $result);

		// Each item should be an object
		$this->assertIsObject($result[0]);
		$this->assertIsObject($result[1]);

		// Can access by numeric index
		$this->assertEquals(1, $result[0]->id);
		$this->assertEquals('batman', $result[0]->displayName);
		$this->assertEquals(2, $result[1]->id);
		$this->assertEquals('superman', $result[1]->displayName);
	}

	/**
	 * Test with actual JSON from database (starting with key "1" not "0")
	 */
	public function testActualDatabaseJsonStructure(): void
	{
		$helper = new NestedDataHelper();
		$helper->addKey('participants');

		// Actual JSON structure from your database
		$jsonString = '{"1":{"id":1,"userId":221,"role":"member","joinedAt":"2025-10-31 23:23:22","lastReadAt":"2025-11-29 18:17:48","lastReadMessageId":514,"createdAt":"2025-10-31 17:23:22","updatedAt":"2025-11-29 18:17:48","deletedAt":null,"displayName":"batman","firstName":"bruce","lastName":"w","email":"test@email.com","image":null,"status":"offline"}}';

		$decoded = json_decode($jsonString, true);
		$result = $helper->getGroupedData($decoded);

		// Should be a numeric array starting from 0
		$this->assertIsArray($result);
		$this->assertCount(1, $result);
		$this->assertArrayHasKey(0, $result);

		// Item should be an object
		$this->assertIsObject($result[0]);

		// Can access properties
		$this->assertEquals(1, $result[0]->id);
		$this->assertEquals(221, $result[0]->userId);
		$this->assertEquals('member', $result[0]->role);
		$this->assertEquals('batman', $result[0]->displayName);
		$this->assertEquals('bruce', $result[0]->firstName);
	}
}
