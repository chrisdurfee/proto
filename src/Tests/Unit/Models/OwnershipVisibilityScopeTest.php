<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Scopes\OwnershipVisibilityScope;
use Proto\Tests\Test;

/**
 * OwnershipVisibilityScopeTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class OwnershipVisibilityScopeTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Multiple AND'd conditions produce the correct SQL/params for the
	 * owning actor (owner branch is still an option, but non-owner
	 * conditions must all be present, AND'd, and parameterized).
	 *
	 * @return void
	 */
	public function testMultipleConditionsProduceAndedSqlAndParams(): void
	{
		$scope = new OwnershipVisibilityScope(
			userField: 'userId',
			visibilityConditions: [
				['enabled', '=', 1],
				['moderationStatus', '=', 'approved'],
				['hiddenAt', 'IS', null]
			],
			alias: 'e',
			adminPermission: null
		);

		$actor = (object)['id' => 42];
		$result = $scope->apply(['title' => 'x'], $actor);

		$this->assertIsArray($result);
		$last = $result[array_key_last($result)];
		$this->assertEquals(
			'(e.user_id = ? OR (e.enabled = ? AND e.moderation_status = ? AND e.hidden_at IS NULL))',
			$last[0]
		);
		$this->assertEquals([42, 1, 'approved'], $last[1]);
	}

	/**
	 * An anonymous/other actor gets the same visibility gate, keyed to
	 * userId = 0.
	 *
	 * @return void
	 */
	public function testAnonymousActorStillGetsVisibilityGate(): void
	{
		$scope = new OwnershipVisibilityScope(
			userField: 'userId',
			visibilityConditions: [
				['enabled', '=', 1],
				['hiddenAt', 'IS', null]
			],
			adminPermission: null
		);

		$result = $scope->apply([], null);
		$last = $result[array_key_last($result)];

		$this->assertEquals('(user_id = ? OR (enabled = ? AND hidden_at IS NULL))', $last[0]);
		$this->assertEquals([0, 1], $last[1]);
	}

	/**
	 * `IS NOT` with a null value produces `IS NOT NULL` with no bound param.
	 *
	 * @return void
	 */
	public function testIsNotNullConditionSupport(): void
	{
		$scope = new OwnershipVisibilityScope(
			visibilityConditions: [['deletedAt', 'IS NOT', null]],
			adminPermission: null
		);

		$result = $scope->apply([], (object)['id' => 1]);
		$last = $result[array_key_last($result)];

		$this->assertEquals('(user_id = ? OR (deleted_at IS NOT NULL))', $last[0]);
		$this->assertEquals([1], $last[1]);
	}

	/**
	 * `IN` with an array value produces a parenthesized, parameterized list.
	 *
	 * @return void
	 */
	public function testInConditionSupport(): void
	{
		$scope = new OwnershipVisibilityScope(
			visibilityConditions: [['category', 'IN', ['a', 'b', 'c']]],
			adminPermission: null
		);

		$result = $scope->apply([], (object)['id' => 5]);
		$last = $result[array_key_last($result)];

		$this->assertEquals('(user_id = ? OR (category IN (?, ?, ?)))', $last[0]);
		$this->assertEquals([5, 'a', 'b', 'c'], $last[1]);
	}

	/**
	 * An empty condition list makes the non-owner branch always match
	 * (owner OR anyone) rather than throwing or emitting invalid SQL.
	 *
	 * @return void
	 */
	public function testEmptyConditionListAlwaysMatchesNonOwnerBranch(): void
	{
		$scope = new OwnershipVisibilityScope(adminPermission: null);

		$result = $scope->apply([], (object)['id' => 9]);
		$last = $result[array_key_last($result)];

		$this->assertEquals('(user_id = ? OR (1 = 1))', $last[0]);
		$this->assertEquals([9], $last[1]);
	}
}
