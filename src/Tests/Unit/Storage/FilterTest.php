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

	/**
	 * Test mixed filter: associative keys combined with numeric-indexed arrays.
	 *
	 * This reproduces the bug where isAssoc() returned true for mixed filters,
	 * causing numeric keys to be prepended to IN arrays and producing broken SQL.
	 *
	 * @return void
	 */
	public function testSetupMixedAssocAndNumericFilters(): void
	{
		$params = [];
		$result = Filter::setup([
			'userId' => 5,
			['status', 'IN', ['active', 'pending']]
		], $params);

		$this->assertCount(2, $result);
		$this->assertEquals('user_id = ?', $result[0]);
		$this->assertEquals('status IN (?, ?)', $result[1]);
		$this->assertEquals([5, 'active', 'pending'], $params);
	}

	/**
	 * Test mixed filter with raw SQL string entry.
	 *
	 * @return void
	 */
	public function testSetupMixedAssocAndRawSql(): void
	{
		$params = [];
		$result = Filter::setup([
			'userId' => 5,
			"deleted_at IS NULL"
		], $params);

		$this->assertCount(2, $result);
		$this->assertEquals('user_id = ?', $result[0]);
		$this->assertEquals('deleted_at IS NULL', $result[1]);
		$this->assertEquals([5], $params);
	}

	/**
	 * Qualify object filters that match model fields.
	 *
	 * @return void
	 */
	public function testQualifyObjectFilterPrefixesModelFields(): void
	{
		$filter = (object)[
			'status' => 'active',
			'partnerId' => 9,
			'makeName' => 'Porsche'
		];

		$result = Filter::qualify($filter, 'ml', ['status', 'partnerId', 'title']);
		$this->assertEquals('active', $result->{'ml.status'});
		$this->assertEquals(9, $result->{'ml.partnerId'});
		$this->assertEquals('Porsche', $result->makeName);
	}

	/**
	 * Already-aliased and raw SQL entries stay untouched.
	 *
	 * @return void
	 */
	public function testQualifyLeavesAliasedAndUnknownColumns(): void
	{
		$filter = [
			['ml.status', 'sold'],
			['firstName', 'Ada'],
			['(p.user_id = ? OR p.privacy = ?)', [1, 'public']]
		];

		$result = Filter::qualify($filter, 'ml', ['status', 'sellerId']);
		$this->assertEquals('ml.status', $result[0][0]);
		$this->assertEquals('firstName', $result[1][0]);
		$this->assertEquals('(p.user_id = ? OR p.privacy = ?)', $result[2][0]);
	}

	/**
	 * Associative array keys that match fields are prefixed.
	 *
	 * @return void
	 */
	public function testQualifyAssociativeArray(): void
	{
		$result = Filter::qualify([
			'status' => 'active',
			'sellerId' => ['IN', [1, 2]]
		], 'ml', ['status', 'sellerId']);

		$this->assertArrayHasKey('ml.status', $result);
		$this->assertArrayHasKey('ml.sellerId', $result);
		$this->assertEquals('active', $result['ml.status']);
	}

	/**
	 * Empty field list is a no-op so unknown models are not rewritten.
	 *
	 * @return void
	 */
	public function testQualifyEmptyFieldsIsNoop(): void
	{
		$filter = ['status' => 'active'];
		$this->assertSame($filter, Filter::qualify($filter, 'ml', []));
	}

	/**
	 * Since-filter binds a validated timestamp.
	 *
	 * @return void
	 */
	public function testSinceBuildsParameterizedCondition(): void
	{
		$result = Filter::since('c', '2026-08-15 03:00:00', 'updatedAt');
		$this->assertEquals('c.updated_at >= ?', $result[0]);
		$this->assertEquals(['2026-08-15 03:00:00'], $result[1]);
	}

	/**
	 * Multiple since columns are OR'd with the same bound value.
	 *
	 * @return void
	 */
	public function testSinceOrsMultipleColumns(): void
	{
		$result = Filter::since('c', '2026-08-15T03:00:00', ['updatedAt', 'createdAt']);
		$this->assertEquals('(c.updated_at >= ? OR c.created_at >= ?)', $result[0]);
		$this->assertEquals(['2026-08-15T03:00:00', '2026-08-15T03:00:00'], $result[1]);
	}

	/**
	 * SQL fragments in lastSync must not become query text.
	 *
	 * @return void
	 */
	public function testSinceRejectsUnsafeTimestamp(): void
	{
		$result = Filter::since('c', "2026-01-01' OR 1=1 --", 'updatedAt');
		$this->assertEquals('1 = 0', $result[0]);
		$this->assertSame([], $result[1]);
		$this->assertFalse(Filter::isSafeTimestamp("'; DROP TABLE users;--"));
		$this->assertTrue(Filter::isSafeTimestamp('2026-08-15'));
	}

	/**
	 * Request filters drop raw SQL strings and keep column tuples.
	 *
	 * @return void
	 */
	public function testSanitizeRequestFilterDropsRawSql(): void
	{
		$filter = (object)[
			'status' => 'active',
			0 => 'privacy = "public" OR 1=1',
			1 => ['sellerId', 9],
			2 => ['(p.user_id = ?)', [1]]
		];

		$result = Filter::sanitizeRequestFilter($filter);
		$this->assertEquals('active', $result->status);
		$values = array_values((array)$result);
		$this->assertContains(['sellerId', 9], $values);
		$this->assertNotContains('privacy = "public" OR 1=1', $values);
	}

	/**
	 * Unsafe column keys are stripped from request filters.
	 *
	 * @return void
	 */
	public function testSanitizeRequestFilterRejectsUnsafeKeys(): void
	{
		$result = Filter::sanitizeRequestFilter([
			'status; DROP TABLE x' => 'a',
			'ml.status' => 'active'
		]);

		$this->assertArrayNotHasKey('status; DROP TABLE x', $result);
		$this->assertEquals('active', $result['ml.status']);
	}

	/**
	 * @return void
	 */
	public function testSinceLiteralQuotesSafeTimestamp(): void
	{
		$sql = Filter::sinceLiteral('c', '2026-08-15 03:00:00', ['updatedAt', 'createdAt']);
		$this->assertEquals(
			"(c.updated_at >= '2026-08-15 03:00:00' OR c.created_at >= '2026-08-15 03:00:00')",
			$sql
		);
		$this->assertNull(Filter::sinceLiteral('c', "2026-01-01' OR 1=1 --"));
	}

	/**
	 * Request filters drop secret columns when an allowlist is passed.
	 *
	 * @return void
	 */
	public function testSanitizeRequestFilterDropsSecretColumns(): void
	{
		$allowed = ['id', 'status'];
		$result = Filter::sanitizeRequestFilter(
			(object)[
				'email' => 'a@b.c',
				'password' => 'x',
				'status' => 'published',
				'token' => 'abc'
			],
			$allowed
		);

		$this->assertEquals('published', $result->status);
		$this->assertFalse(isset($result->email));
		$this->assertFalse(isset($result->password));
		$this->assertFalse(isset($result->token));
	}

	/**
	 * Client IN lists longer than 100 are truncated; short lists stay.
	 *
	 * @return void
	 */
	public function testSanitizeRequestFilterCapsInLists(): void
	{
		$ids = range(1, 101);
		$result = Filter::sanitizeRequestFilter([
			['id', 'IN', $ids],
			['status', 'IN', ['a', 'b', 'c']]
		]);

		$values = array_values($result);
		$this->assertCount(2, $values);
		$this->assertSame(['status', 'IN', ['a', 'b', 'c']], $values[1]);
		$this->assertSame('IN', $values[0][1]);
		$this->assertCount(Filter::REQUEST_IN_LIMIT, $values[0][2]);
		$this->assertSame(range(1, 100), $values[0][2]);
	}

	/**
	 * App-built filters after sanitize stay uncapped.
	 *
	 * @return void
	 */
	public function testAppBuiltInListsAreUncapped(): void
	{
		$params = [];
		$result = Filter::format(['id', 'IN', range(1, 101)], $params, false);
		$this->assertStringStartsWith('id IN (', $result);
		$this->assertCount(101, $params);
	}
}
