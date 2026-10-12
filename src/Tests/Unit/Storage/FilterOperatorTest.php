<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use Proto\Storage\Filter;
use Proto\Tests\Test;

/**
 * FilterOperatorTest
 *
 * Operator tuples through Filter::format()/setup(): every user value
 * must end up in $params, never in the SQL fragment.
 *
 * @package Proto\Tests\Unit\Storage
 */
final class FilterOperatorTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Hostile value used to prove values are bound, not inlined.
	 */
	private const EVIL = "1' OR '1'='1";

	/**
	 * @param array $tuple
	 * @param array $params
	 * @return mixed
	 */
	private function format(array $tuple, array &$params = []): mixed
	{
		return Filter::format($tuple, $params, false);
	}

	/**
	 * @return void
	 */
	public function testLikeBindsPattern(): void
	{
		$params = [];
		$this->assertSame('name LIKE ?', $this->format(['name', 'LIKE', '%' . self::EVIL . '%'], $params));
		$this->assertSame(['%' . self::EVIL . '%'], $params);
	}

	/**
	 * @return void
	 */
	public function testOperatorsAreCaseInsensitive(): void
	{
		$params = [];
		$this->assertSame('name LIKE ?', $this->format(['name', 'like', '%a%'], $params));
		$this->assertSame('name NOT LIKE ?', $this->format(['name', ' not like ', '%b%'], $params));
		$this->assertSame('id IN (?, ?)', $this->format(['id', 'in', [1, 2]], $params));
		$this->assertSame(['%a%', '%b%', 1, 2], $params);
	}

	/**
	 * @return void
	 */
	public function testUnknownOperatorFallsBackToEquals(): void
	{
		$params = [];
		$this->assertSame('id = ?', $this->format(['id', '= 1 OR 1 =', self::EVIL], $params));
		$this->assertSame([self::EVIL], $params);
	}

	/**
	 * @return void
	 */
	public function testComparisonOperators(): void
	{
		foreach (['<', '>', '<=', '>=', '!='] as $op)
		{
			$params = [];
			$this->assertSame("age {$op} ?", $this->format(['age', $op, 30], $params));
			$this->assertSame([30], $params);
		}
	}

	/**
	 * @return void
	 */
	public function testInBindsEveryValueAndReindexes(): void
	{
		$params = ['before'];
		$sql = $this->format(['status', 'IN', ['a' => 'x', 'b' => self::EVIL]], $params);

		$this->assertSame('status IN (?, ?)', $sql);
		$this->assertSame(['before', 'x', self::EVIL], $params);
	}

	/**
	 * @return void
	 */
	public function testNotInWithEmptyListMatchesEverything(): void
	{
		$params = [];
		$this->assertSame('1 = 1', $this->format(['id', 'NOT IN', []], $params));
		$this->assertSame('1 = 0', $this->format(['id', 'IN', []], $params));
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testBetweenBindsBothBounds(): void
	{
		$params = [];
		$sql = $this->format(['created_at', 'BETWEEN', ['2026-01-01', self::EVIL]], $params);

		$this->assertSame('created_at BETWEEN ? AND ?', $sql);
		$this->assertSame(['2026-01-01', self::EVIL], $params);
	}

	/**
	 * @return void
	 */
	public function testNotBetween(): void
	{
		$params = [];
		$this->assertSame('score NOT BETWEEN ? AND ?', $this->format(['score', 'not between', [1, 5]], $params));
		$this->assertSame([1, 5], $params);
	}

	/**
	 * @return void
	 */
	public function testMalformedBetweenMatchesNothing(): void
	{
		$params = [];
		$this->assertSame('1 = 0', $this->format(['score', 'BETWEEN', [1]], $params));
		$this->assertSame('1 = 0', $this->format(['score', 'BETWEEN', 'x'], $params));
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testNullWithNegatedOperatorsIsNotNull(): void
	{
		foreach (['!=', '<>', 'NOT', 'IS NOT', 'is not null'] as $op)
		{
			$params = [];
			$this->assertSame('deleted_at IS NOT NULL', $this->format(['deleted_at', $op, null], $params), $op);
			$this->assertSame([], $params);
		}
	}

	/**
	 * @return void
	 */
	public function testNullWithOtherOperatorsIsNull(): void
	{
		$params = [];
		$this->assertSame('deleted_at IS NULL', $this->format(['deleted_at', '=', null], $params));
		$this->assertSame('deleted_at IS NULL', $this->format(['deleted_at', 'IS', null], $params));
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testIsNullOperatorBindsNothing(): void
	{
		$params = [];
		$this->assertSame('deleted_at IS NULL', $this->format(['deleted_at', 'IS NULL', true], $params));
		$this->assertSame('deleted_at IS NOT NULL', $this->format(['deleted_at', 'is not null', 1], $params));
		$this->assertSame([], $params);
	}

	/**
	 * Over-long tuples must not leave an orphan param that shifts later
	 * placeholders.
	 *
	 * @return void
	 */
	public function testOverlongTupleBindsNothing(): void
	{
		$params = [];
		$this->assertSame('1 = 0', $this->format(['a', '=', 'b', 'c'], $params));
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testColumnNameIsSanitised(): void
	{
		$params = [];
		$this->assertSame('idDROPTABLEusers = ?', $this->format(['id; DROP TABLE users', 1], $params));
	}

	/**
	 * @return void
	 */
	public function testSnakeCaseConversion(): void
	{
		$params = [];
		$this->assertSame('created_at > ?', Filter::format(['createdAt', '>', '2026-01-01'], $params));
	}

	/**
	 * @return void
	 */
	public function testSetupKeepsParamsAlignedWithPlaceholders(): void
	{
		$params = [];
		$filters = Filter::setup((object)[
			'status' => ['IN', ['a', 'b']],
			'deletedAt' => null,
			'score' => ['BETWEEN', [1, 9]],
			'name' => ['like', '%x%'],
			'userId' => 7,
		], $params);

		$this->assertSame([
			'status IN (?, ?)',
			'deleted_at IS NULL',
			'score BETWEEN ? AND ?',
			'name LIKE ?',
			'user_id = ?',
		], $filters);
		$this->assertSame(['a', 'b', 1, 9, '%x%', 7], $params);
		$this->assertSame(substr_count(implode(' ', $filters), '?'), count($params));
	}

	/**
	 * @return void
	 */
	public function testSetupWithEmptyFilterReturnsNothing(): void
	{
		$params = [];
		$this->assertSame([], Filter::setup(null, $params));
		$this->assertSame([], Filter::setup([], $params));
		$this->assertSame([], Filter::setup((object)[], $params));
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testFormatForSetBindsValuesButKeepsFunctions(): void
	{
		$params = [];
		$set = Filter::formatForSet(['displayName' => self::EVIL, 'updatedAt' => 'NOW()', 'note' => 'NOW() OR 1'], $params);

		$this->assertSame(['display_name' => '?', 'updated_at' => 'NOW()', 'note' => '?'], $set);
		$this->assertSame([self::EVIL, 'NOW() OR 1'], $params);
	}

	/**
	 * @return void
	 */
	public function testExistsSubqueryBindsConditions(): void
	{
		[$sql, $params] = Filter::exists('orders', 'o', 'o.user_id = u.id', [
			['o.status', 'IN', ['paid', 'shipped']],
			['o.total', '>', 10],
			['o.note', self::EVIL],
		]);

		$this->assertSame(
			'EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id AND o.status IN (?, ?) AND o.total > ? AND o.note = ?)',
			$sql
		);
		$this->assertSame(['paid', 'shipped', 10, self::EVIL], $params);
	}

	/**
	 * @return void
	 */
	public function testNotExistsAndAliasedHelpers(): void
	{
		[$sql] = Filter::notExists('bans', 'b', 'b.user_id = u.id');
		$this->assertSame('NOT EXISTS (SELECT 1 FROM bans b WHERE b.user_id = u.id)', $sql);

		$this->assertSame(['u.created_at', '>', 5], Filter::aliased('u', 'createdAt', 5, '>'));
		$this->assertSame(['u.status', 'x'], Filter::aliased('u', 'status', 'x'));
		$this->assertSame('u.deleted_at IS NULL', Filter::condition('u', 'deletedAt', 'IS NULL'));
	}
}
