<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Policies\ModelPolicy;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ModelPolicyScopeAndInvalidationTest
 *
 * Covers cache-key completeness (route params), shared-scope opt-in for
 * custom GET methods, invalidation after custom writes, and that error
 * responses are never stored.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class ModelPolicyScopeAndInvalidationTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		require_once dirname((new \ReflectionClass(Test::class))->getFileName()) . '/Helpers/TestGlobals.php';
		parent::setUp();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		unset($GLOBALS['protoTestActor']);
		parent::tearDown();
	}

	/**
	 * @param array $params
	 * @return Request
	 */
	private function request(array $params): Request
	{
		$request = new Request();
		$request->setParams((object)$params);
		return $request;
	}

	/**
	 * `/user/:userId/posts` for two users must not share a key.
	 *
	 * @return void
	 */
	public function testGenericKeyIncludesRouteParams(): void
	{
		$policy = new ScopeRecordingPolicy(new ScopeTestController());
		$GLOBALS['protoTestActor'] = (object)['id' => 1];

		$policy->userPosts($this->request(['userId' => '5']));
		$policy->userPosts($this->request(['userId' => '9']));

		$this->assertCount(2, $policy->keys);
		$this->assertNotSame($policy->keys[0], $policy->keys[1]);
		$this->assertStringContainsString('route.userId:5', $policy->keys[0]);
	}

	/**
	 * get() keys include the parent route param and end with the row's
	 * generation, so every variant of the row retires together.
	 *
	 * @return void
	 */
	public function testGetKeyIncludesParentRouteParam(): void
	{
		$policy = new ScopeRecordingPolicy(new ScopeTestController());
		$GLOBALS['protoTestActor'] = (object)['id' => 1];

		$policy->get($this->request(['vehicleId' => '1', 'id' => '5']));
		$policy->get($this->request(['vehicleId' => '2', 'id' => '5']));

		$this->assertNotSame($policy->keys[0], $policy->keys[1]);
		$this->assertMatchesRegularExpression('/:get:5:rp=vehicleId(%3D|=)1:r\d+$/', $policy->keys[0]);
	}

	/**
	 * Custom GET keys carry the list generation, so a write retires them
	 * without scanning the keyspace.
	 *
	 * @return void
	 */
	public function testGenericKeyChangesAfterListGenerationBump(): void
	{
		$policy = new GenerationRecordingPolicy(new ScopeTestController());
		$GLOBALS['protoTestActor'] = (object)['id' => 1];

		$policy->stats($this->request([]));
		$policy->bumpList();
		$policy->stats($this->request([]));

		$this->assertNotSame($policy->keys[0], $policy->keys[1]);
		$this->assertMatchesRegularExpression('/:stats:g\d+:/', $policy->keys[0]);
	}

	/**
	 * A row's get() keys change once its identity is invalidated, and
	 * other rows keep theirs.
	 *
	 * @return void
	 */
	public function testGetKeyChangesAfterRowInvalidation(): void
	{
		$policy = new GenerationRecordingPolicy(new ScopeTestController());
		$GLOBALS['protoTestActor'] = (object)['id' => 1];

		$policy->get($this->request(['id' => '5']));
		$policy->get($this->request(['id' => '6']));
		$policy->invalidateRow(5);
		$policy->get($this->request(['id' => '5']));
		$policy->get($this->request(['id' => '6']));

		$this->assertNotSame($policy->keys[0], $policy->keys[2]);
		$this->assertSame($policy->keys[1], $policy->keys[3]);
	}

	/**
	 * A guest without a session cookie gets a new session id per request,
	 * so per-session keys are never built for them. Shared keys still are.
	 *
	 * @return void
	 */
	public function testCookielessGuestSkipsPerSessionKeys(): void
	{
		unset($GLOBALS['protoTestActor']);
		$policy = new ScopeRecordingPolicy(new ScopeTestController());

		$policy->get($this->request(['id' => '5']));
		$policy->fitsVehicle($this->request([]));
		$this->assertSame([], $policy->keys);

		$policy->stats($this->request([]));
		$this->assertCount(1, $policy->keys);
		$this->assertStringContainsString(':shared:', $policy->keys[0]);
	}

	/**
	 * A custom GET on a shared controller is per-user unless listed.
	 *
	 * @return void
	 */
	public function testUnlistedCustomGetIsUserScopedOnSharedController(): void
	{
		$policy = new ScopeRecordingPolicy(new ScopeTestController());

		$GLOBALS['protoTestActor'] = (object)['id' => 1];
		$policy->fitsVehicle($this->request([]));
		$GLOBALS['protoTestActor'] = (object)['id' => 2];
		$policy->fitsVehicle($this->request([]));

		$this->assertNotSame($policy->keys[0], $policy->keys[1]);
		$this->assertStringNotContainsString(':shared:', $policy->keys[0]);
	}

	/**
	 * A custom GET listed in sharedCacheMethods() uses the shared scope.
	 *
	 * @return void
	 */
	public function testListedCustomGetIsShared(): void
	{
		$policy = new ScopeRecordingPolicy(new ScopeTestController());

		$GLOBALS['protoTestActor'] = (object)['id' => 1];
		$policy->stats($this->request([]));
		$GLOBALS['protoTestActor'] = (object)['id' => 2];
		$policy->stats($this->request([]));

		$this->assertSame($policy->keys[0], $policy->keys[1]);
		$this->assertStringContainsString(':shared:', $policy->keys[0]);
	}

	/**
	 * A non-GET custom method invalidates before and after the write.
	 *
	 * @return void
	 */
	public function testCustomWriteInvalidates(): void
	{
		$controller = new ScopeTestController();
		$policy = new ScopeRecordingPolicy($controller);
		$policy->getRequest = false;

		$policy->toggleAlerts($this->request(['id' => '5']));

		$this->assertTrue($controller->toggled);
		$this->assertSame(2, $policy->deleteAllCalls);
		$this->assertSame([5, 5], $policy->invalidatedIds);
	}

	/**
	 * Error responses are never stored.
	 *
	 * @return void
	 */
	public function testErrorResponsesAreNotCached(): void
	{
		$policy = new ScopeRecordingPolicy(new ScopeTestController());

		$this->assertTrue($policy->exposeIsError((object)['success' => false, 'message' => 'nope']));
		$this->assertTrue($policy->exposeIsError((object)['code' => 403]));
		$this->assertFalse($policy->exposeIsError((object)['success' => true, 'rows' => []]));
		$this->assertFalse($policy->exposeIsError('plain'));
	}
}

/**
 * Records keys and invalidation calls without touching a cache driver.
 */
final class ScopeRecordingPolicy extends ModelPolicy
{
	/** @var array<int, string> */
	public array $keys = [];

	/** @var array<int, mixed> */
	public array $invalidatedIds = [];

	public int $deleteAllCalls = 0;

	public bool $getRequest = true;

	protected function createKey(string $method, mixed $params): string
	{
		$key = parent::createKey($method, $params);
		$this->keys[] = $key;
		return $key;
	}

	protected function getValueAndTag(string $key): array
	{
		return [null, null];
	}

	protected function acquireLock(string $key): bool
	{
		return true;
	}

	protected function releaseLock(string $key): void
	{
	}

	protected function storeRemembered(string $key, mixed $store, int $expire, bool $stripAndReenrich): void
	{
	}

	protected function isGetRequest(): bool
	{
		return $this->getRequest;
	}

	protected function deleteAll(): void
	{
		$this->deleteAllCalls++;
	}

	protected function invalidateGetKeys(Request $request, mixed $id = null, ?object $item = null): void
	{
		$this->invalidatedIds[] = $id;
	}

	public function exposeIsError(mixed $response): bool
	{
		return $this->isErrorResponse($response);
	}
}

/**
 * Records keys and uses real generation bookkeeping (in-process fallback).
 */
final class GenerationRecordingPolicy extends ModelPolicy
{
	/** @var array<int, string> */
	public array $keys = [];

	protected function createKey(string $method, mixed $params): string
	{
		$key = parent::createKey($method, $params);
		$this->keys[] = $key;
		return $key;
	}

	protected function getValueAndTag(string $key): array
	{
		return [null, null];
	}

	protected function acquireLock(string $key): bool
	{
		return true;
	}

	protected function releaseLock(string $key): void
	{
	}

	protected function storeRemembered(string $key, mixed $store, int $expire, bool $stripAndReenrich): void
	{
	}

	protected function isGetRequest(): bool
	{
		return true;
	}

	public function bumpList(): void
	{
		$this->deleteAll();
	}

	public function invalidateRow(mixed $id): void
	{
		$this->invalidateGetKeys(new Request(), $id);
	}
}

/**
 * Shared-cache controller with listed and unlisted custom methods.
 */
final class ScopeTestController extends ResourceController
{
	public bool $toggled = false;

	public function __construct()
	{
		$this->model = ScopeTestModel::class;
		$this->cacheSharedPayload = true;
		$this->sharedCacheMethods = ['stats'];
		parent::__construct();
	}

	public function get(Request $request): object
	{
		return (object)['success' => true, 'row' => null];
	}

	public function userPosts(Request $request): object
	{
		return (object)['success' => true, 'rows' => []];
	}

	public function fitsVehicle(Request $request): object
	{
		return (object)['success' => true, 'rows' => []];
	}

	public function stats(Request $request): object
	{
		return (object)['success' => true, 'rows' => []];
	}

	public function toggleAlerts(Request $request): object
	{
		$this->toggled = true;
		return (object)['success' => true];
	}
}

/**
 * Minimal model for the scope tests.
 */
final class ScopeTestModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'scope_test';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title'];
}
