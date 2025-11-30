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
}
