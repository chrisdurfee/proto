<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use Proto\Storage\Filter;
use Proto\Tests\Test;

/**
 * FilterTest
 *
 * Tests for the Filter class, including IN/NOT IN array support.
 *
 * @package Proto\Tests\Unit\Storage
 */
final class FilterTest extends Test
{
	/**
	 * Test basic equality filter.
	 *
	 * @return void
	 */
	public function testBasicEqualityFilter(): void
	{
		$params = [];
		$result = Filter::format(['user_id', 5], $params, false);
		$this->assertEquals('user_id = ?', $result);
		$this->assertEquals([5], $params);
	}

	/**
	 * Test operator filter.
	 *
	 * @return void
	 */
	public function testOperatorFilter(): void
	{
		$params = [];
		$result = Filter::format(['age', '>', 18], $params, false);
		$this->assertEquals('age > ?', $result);
		$this->assertEquals([18], $params);
	}

	/**
	 * Test null value produces IS NULL.
	 *
	 * @return void
	 */
	public function testNullFilter(): void
	{
		$params = [];
		$result = Filter::format(['deleted_at', null], $params, false);
		$this->assertEquals('deleted_at IS NULL', $result);
		$this->assertEmpty($params);
	}

	/**
	 * Test not-null filter.
	 *
	 * @return void
	 */
	public function testNotNullFilter(): void
	{
		$params = [];
		$result = Filter::format(['deleted_at', '!=', null], $params, false);
		$this->assertEquals('deleted_at IS NOT NULL', $result);
		$this->assertEmpty($params);
	}

	/**
	 * Test IN filter with array of values.
	 *
	 * @return void
	 */
	public function testInFilterWithArray(): void
	{
		$params = [];
		$result = Filter::format(['reply_id', 'IN', [1, 2, 3]], $params, false);
		$this->assertEquals('reply_id IN (?, ?, ?)', $result);
		$this->assertEquals([1, 2, 3], $params);
	}

	/**
	 * Test NOT IN filter with array of values.
	 *
	 * @return void
	 */
	public function testNotInFilterWithArray(): void
	{
		$params = [];
		$result = Filter::format(['status', 'NOT IN', ['banned', 'deleted']], $params, false);
		$this->assertEquals('status NOT IN (?, ?)', $result);
		$this->assertEquals(['banned', 'deleted'], $params);
	}

	/**
	 * Test IN filter with single value array.
	 *
	 * @return void
	 */
	public function testInFilterWithSingleValue(): void
	{
		$params = [];
		$result = Filter::format(['id', 'IN', [42]], $params, false);
		$this->assertEquals('id IN (?)', $result);
		$this->assertEquals([42], $params);
	}

	/**
	 * Test IN filter with empty array returns false condition.
	 *
	 * @return void
	 */
	public function testInFilterWithEmptyArray(): void
	{
		$params = [];
		$result = Filter::format(['id', 'IN', []], $params, false);
		$this->assertEquals('1 = 0', $result);
		$this->assertEmpty($params);
	}

	/**
	 * Test NOT IN filter with empty array returns true condition.
	 *
	 * @return void
	 */
	public function testNotInFilterWithEmptyArray(): void
	{
		$params = [];
		$result = Filter::format(['id', 'NOT IN', []], $params, false);
		$this->assertEquals('1 = 1', $result);
		$this->assertEmpty($params);
	}

	/**
	 * Test IN filter with table alias.
	 *
	 * @return void
	 */
	public function testInFilterWithAlias(): void
	{
		$params = [];
		$result = Filter::format(['a.user_id', 'IN', [10, 20, 30]], $params, false);
		$this->assertEquals('a.user_id IN (?, ?, ?)', $result);
		$this->assertEquals([10, 20, 30], $params);
	}

	/**
	 * Test IN filter works inside setup() with indexed filters.
	 *
	 * @return void
	 */
	public function testSetupWithInFilter(): void
	{
		$params = [];
		$result = Filter::setup([
			['user_id', 5],
			['reply_id', 'IN', [1, 2, 3]]
		], $params, false);

		$this->assertCount(2, $result);
		$this->assertEquals('user_id = ?', $result[0]);
		$this->assertEquals('reply_id IN (?, ?, ?)', $result[1]);
		$this->assertEquals([5, 1, 2, 3], $params);
	}

	/**
	 * Test IN filter works inside setup() with associative filters.
	 *
	 * @return void
	 */
	public function testSetupAssociativeWithInFilter(): void
	{
		$params = [];
		$result = Filter::setup([
			'userId' => 5,
			'replyId' => ['IN', [10, 20]]
		], $params);

		$this->assertCount(2, $result);
		$this->assertEquals('user_id = ?', $result[0]);
		$this->assertEquals('reply_id IN (?, ?)', $result[1]);
		$this->assertEquals([5, 10, 20], $params);
	}

	/**
	 * Test raw SQL filter still works.
	 *
	 * @return void
	 */
	public function testRawSqlFilter(): void
	{
		$params = [];
		$result = Filter::format("status = 'active'", $params, false);
		$this->assertEquals("status = 'active'", $result);
		$this->assertEmpty($params);
	}

	/**
	 * Test raw SQL with params still works.
	 *
	 * @return void
	 */
	public function testRawSqlWithParams(): void
	{
		$params = [];
		$result = Filter::format(["id IN (?, ?)", [1, 2]], $params, false);
		$this->assertEquals("id IN (?, ?)", $result);
		$this->assertEquals([1, 2], $params);
	}
}
