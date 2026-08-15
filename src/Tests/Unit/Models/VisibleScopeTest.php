<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Scopes\VisibleScope;
use Proto\Tests\Test;

/**
 * VisibleScopeTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class VisibleScopeTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testApplyAddsOwnerOrPublicPublishedClause(): void
	{
		$scope = new VisibleScope(
			userField: 'userId',
			privacyField: 'privacy',
			publicValue: 'public',
			statusField: 'status',
			publishedValue: 'published',
			alias: 'p',
			adminPermission: null
		);

		$actor = (object)['id' => 42];
		$result = $scope->apply(['status' => 'published'], $actor);

		$this->assertIsArray($result);
		$last = $result[array_key_last($result)];
		$this->assertEquals('(p.user_id = ? OR (p.privacy = ? AND p.status = ?))', $last[0]);
		$this->assertEquals([42, 'public', 'published'], $last[1]);
	}

	/**
	 * @return void
	 */
	public function testAnonymousActorStillGetsPublicGate(): void
	{
		$scope = new VisibleScope(adminPermission: null);
		$result = $scope->apply([], null);
		$last = $result[array_key_last($result)];
		$this->assertEquals([0, 'public', 'published'], $last[1]);
	}
}
