<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Auth;

use Proto\Auth;
use Proto\Auth\Policies\Policy;
use Proto\Http\Router\Request;
use Proto\Tests\Test;
use Proto\Tests\Unit\Auth\Fixtures\GroupPostPolicy;

/**
 * PolicyHelpersTest
 *
 * Ownership, route-user, role/permission and scope helpers on the base
 * Policy. The acting user comes from session()->user (TestGlobals) and
 * role/permission gates from the auth() registry.
 *
 * @package Proto\Tests\Unit\Auth
 */
final class PolicyHelpersTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var array<string, mixed>
	 */
	private array $savedGates = [];

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		require_once dirname((new \ReflectionClass(Test::class))->getFileName()) . '/Helpers/TestGlobals.php';
		parent::setUp();

		foreach (['user', 'role', 'permission'] as $gate)
		{
			$this->savedGates[$gate] = Auth::has($gate) ? Auth::get($gate) : null;
			Auth::delete($gate);
		}
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		unset($GLOBALS['protoTestActor']);
		foreach ($this->savedGates as $gate => $value)
		{
			Auth::delete($gate);
			if ($value !== null)
			{
				Auth::set($gate, $value);
			}
		}
		parent::tearDown();
	}

	/**
	 * @param int|string|null $id
	 * @return void
	 */
	private function actAs(int|string|null $id): void
	{
		$GLOBALS['protoTestActor'] = ($id === null) ? null : (object)['id' => $id];
	}

	/**
	 * @return Policy
	 */
	private function policy(): Policy
	{
		return new class extends Policy
		{
			public function expose(string $method, mixed ...$args): mixed
			{
				return $this->{$method}(...$args);
			}
		};
	}

	/**
	 * @param array<string, bool> $roles
	 * @return void
	 */
	private function grantRoles(array $roles): void
	{
		Auth::set('role', new class($roles)
		{
			public function __construct(private array $roles) {}

			public function hasRole(string $slug): bool
			{
				return $this->roles[$slug] ?? false;
			}
		});
	}

	/**
	 * @return void
	 */
	public function testOwnsResourceRequiresSignedInUser(): void
	{
		$this->actAs(null);
		$this->assertFalse($this->policy()->expose('ownsResource', 5));
	}

	/**
	 * @return void
	 */
	public function testOwnsResourceComparesIds(): void
	{
		$this->actAs(5);
		$policy = $this->policy();

		$this->assertTrue($policy->expose('ownsResource', 5));
		$this->assertFalse($policy->expose('ownsResource', 6));
		$this->assertFalse($policy->expose('ownsResource', null));
	}

	/**
	 * Session ids frequently arrive as strings from the DB.
	 *
	 * @return void
	 */
	public function testOwnsResourceNormalisesStringSessionId(): void
	{
		$this->actAs('42');
		$this->assertTrue($this->policy()->expose('ownsResource', 42));
	}

	/**
	 * @return void
	 */
	public function testMatchesRouteUser(): void
	{
		$this->actAs(7);
		$policy = $this->policy();

		$this->assertTrue($policy->expose('matchesRouteUser', new Request((object)['userId' => '7'])));
		$this->assertFalse($policy->expose('matchesRouteUser', new Request((object)['userId' => '8'])));
	}

	/**
	 * @return void
	 */
	public function testMatchesRouteUserCustomParamName(): void
	{
		$this->actAs(7);
		$request = new Request((object)['ownerId' => '7', 'userId' => '9']);

		$this->assertTrue($this->policy()->expose('matchesRouteUser', $request, 'ownerId'));
	}

	/**
	 * @return void
	 */
	public function testMatchesRouteUserDeniesMissingOrZeroParam(): void
	{
		$this->actAs(7);
		$policy = $this->policy();

		$this->assertFalse($policy->expose('matchesRouteUser', new Request()));
		$this->assertFalse($policy->expose('matchesRouteUser', new Request((object)['userId' => '0'])));
		$this->assertFalse($policy->expose('matchesRouteUser', new Request((object)['userId' => 'abc'])));
	}

	/**
	 * @return void
	 */
	public function testGetRouteParamOnlyReturnsPositiveInts(): void
	{
		$policy = $this->policy();
		$request = new Request((object)['groupId' => '12', 'zero' => '0', 'neg' => '-4', 'text' => 'x']);

		$this->assertSame(12, $policy->expose('getRouteParam', $request, 'groupId'));
		$this->assertNull($policy->expose('getRouteParam', $request, 'zero'));
		$this->assertNull($policy->expose('getRouteParam', $request, 'neg'));
		$this->assertNull($policy->expose('getRouteParam', $request, 'text'));
		$this->assertNull($policy->expose('getRouteParam', $request, 'missing'));
	}

	/**
	 * @return void
	 */
	public function testGetResourceIdReadsRouteParam(): void
	{
		$policy = $this->policy();

		$this->assertSame(15, $policy->expose('getResourceId', new Request((object)['id' => '15'])));
		$this->assertNull($policy->expose('getResourceId', new Request((object)['id' => 'abc'])));
	}

	/**
	 * @return void
	 */
	public function testIsSignedInFallsBackToSession(): void
	{
		$policy = $this->policy();

		$this->actAs(null);
		$this->assertFalse($policy->expose('isSignedIn'));

		$this->actAs(3);
		$this->assertTrue($policy->expose('isSignedIn'));
	}

	/**
	 * @return void
	 */
	public function testIsSignedInPrefersUserGate(): void
	{
		$this->actAs(3);
		Auth::set('user', new class
		{
			public function isSignedIn(): bool
			{
				return false;
			}
		});

		$this->assertFalse($this->policy()->expose('isSignedIn'));
	}

	/**
	 * @return void
	 */
	public function testHasRoleWithoutGateDenies(): void
	{
		$this->assertFalse($this->policy()->expose('hasRole', 'admin'));
	}

	/**
	 * @return void
	 */
	public function testHasRoleDelegatesToGate(): void
	{
		$this->grantRoles(['editor' => true]);
		$policy = $this->policy();

		$this->assertTrue($policy->expose('hasRole', 'editor'));
		$this->assertFalse($policy->expose('hasRole', 'admin'));
	}

	/**
	 * A gate object lacking the expected method must not grant access.
	 *
	 * @return void
	 */
	public function testGateWithoutMethodDenies(): void
	{
		Auth::set('role', new \stdClass());
		Auth::set('permission', new \stdClass());
		$policy = $this->policy();

		$this->assertFalse($policy->expose('hasRole', 'admin'));
		$this->assertFalse($policy->expose('hasPermission', 'posts.edit'));
	}

	/**
	 * @return void
	 */
	public function testHasPermissionDelegatesToGate(): void
	{
		Auth::set('permission', new class
		{
			public function hasPermission(string $slug): bool
			{
				return $slug === 'posts.edit';
			}
		});
		$policy = $this->policy();

		$this->assertTrue($policy->expose('hasPermission', 'posts.edit'));
		$this->assertFalse($policy->expose('hasPermission', 'posts.delete'));
	}

	/**
	 * @return void
	 */
	public function testIsOwnerOrAdmin(): void
	{
		$this->actAs(1);
		$policy = $this->policy();

		$this->assertTrue($policy->expose('isOwnerOrAdmin', 1));
		$this->assertFalse($policy->expose('isOwnerOrAdmin', 2));
		$this->assertFalse($policy->expose('isOwnerOrAdmin', null));

		$this->grantRoles(['admin' => true]);
		$this->assertTrue($policy->expose('isOwnerOrAdmin', 2));
		$this->assertTrue($policy->expose('isOwnerOrAdmin', null));
	}

	/**
	 * @return void
	 */
	public function testIsOwnerOrAdminCustomRole(): void
	{
		$this->actAs(1);
		$this->grantRoles(['admin' => true]);

		$this->assertFalse($this->policy()->expose('isOwnerOrAdmin', 2, 'moderator'));
	}

	/**
	 * @return void
	 */
	public function testIsPublicOrOwner(): void
	{
		$this->actAs(1);
		$policy = $this->policy();

		$this->assertTrue($policy->expose('isPublicOrOwner', 'public', 99));
		$this->assertTrue($policy->expose('isPublicOrOwner', 'published', null));
		$this->assertFalse($policy->expose('isPublicOrOwner', 'private', 99));
		$this->assertTrue($policy->expose('isPublicOrOwner', 'private', 1));
		$this->assertFalse($policy->expose('isPublicOrOwner', 'PUBLIC', 99), 'Visibility match is strict.');
		$this->assertTrue($policy->expose('isPublicOrOwner', 'open', 99, ['open']));
	}

	/**
	 * @return void
	 */
	public function testSetupDelegatesToUpdate(): void
	{
		$policy = new class extends Policy
		{
			public function update(Request $request): bool
			{
				return ($request->params()->id ?? null) === '1';
			}
		};

		$this->assertTrue($policy->setup(new Request((object)['id' => '1'])));
		$this->assertFalse($policy->setup(new Request((object)['id' => '2'])));
	}

	/**
	 * @return void
	 */
	public function testSetupFallsBackToDefaultThenDenies(): void
	{
		$withDefault = new class extends Policy
		{
			public function default(): bool
			{
				return true;
			}
		};
		$bare = new class extends Policy {};

		$this->assertTrue($withDefault->setup(new Request()));
		$this->assertFalse($bare->setup(new Request()));
	}

	/**
	 * @return void
	 */
	public function testScopeReturnsFilterUnchangedByDefault(): void
	{
		$filter = (object)['status' => 'active'];
		$this->assertSame($filter, $this->policy()->scope($filter, new Request()));
		$this->assertNull($this->policy()->scope(null, new Request()));
	}

	/**
	 * @return void
	 */
	public function testTypeResolvesFromClassName(): void
	{
		$this->assertSame('groupPost', (new GroupPostPolicy())->exposedType());
	}
}
