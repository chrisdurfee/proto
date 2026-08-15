<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Policies\ModelPolicy;
use Proto\Controllers\Controller;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Models\Scopes\VisibleScope;
use Proto\Tests\Test;

/**
 * ModelPolicyKeysTest
 *
 * @package Proto\Tests\Unit\Cache
 */
final class ModelPolicyKeysTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		unset($GLOBALS['protoTestActor']);
		parent::tearDown();
	}

	/**
	 * @return ModelPolicy
	 */
	private function policy(): ModelPolicy
	{
		$controller = new class extends Controller {};
		return new class($controller) extends ModelPolicy
		{
			public function exposePattern(string $method, mixed $params): string
			{
				return $this->createKeyPattern($method, $params);
			}

			public function exposeIdentities(Request $request, mixed $id = null, ?object $item = null): array
			{
				$identities = [];
				if ($id !== null && $id !== '')
				{
					$identities[] = $id;
				}

				if ($item !== null)
				{
					foreach (['id', 'guid', 'slug', 'uuid'] as $field)
					{
						$value = $item->$field ?? null;
						if ($value !== null && $value !== '')
						{
							$identities[] = $value;
						}
					}
				}

				$raw = $request->input('id') ?? $request->params()->id ?? null;
				if ($raw !== null && $raw !== '')
				{
					$identities[] = $raw;
				}

				return array_values(array_unique($identities, SORT_REGULAR));
			}
		};
	}

	/**
	 * @return void
	 */
	public function testGetPatternDoesNotCoverIncludeSuffix(): void
	{
		$policy = $this->policy();
		$base = $policy->exposePattern('get', 5);
		$this->assertStringEndsWith(':get:5', $base);
		$this->assertStringEndsWith(':get:5:*', $base . ':*');
	}

	/**
	 * @return void
	 */
	public function testGetPatternKeepsSlugIdentity(): void
	{
		$policy = $this->policy();
		$this->assertStringEndsWith(':get:my-slug', $policy->exposePattern('get', 'my-slug'));
	}

	/**
	 * @return void
	 */
	public function testInvalidateCollectsSlugAndNumericIdentities(): void
	{
		$previous = $_REQUEST['id'] ?? null;
		$_GET['id'] = 'my-slug';
		$_REQUEST['id'] = 'my-slug';

		try
		{
			$identities = $this->policy()->exposeIdentities(
				new Request(),
				12,
				(object)['id' => 12, 'slug' => 'my-slug', 'guid' => 'abc']
			);
			$this->assertContains(12, $identities);
			$this->assertContains('my-slug', $identities);
			$this->assertContains('abc', $identities);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['id'], $_REQUEST['id']);
			}
			else
			{
				$_GET['id'] = $previous;
				$_REQUEST['id'] = $previous;
			}
		}
	}

	/**
	 * Shared all() keys must include applyListScopes() so user A's
	 * owner-inclusive list cannot be served to user B.
	 *
	 * @return void
	 */
	public function testSharedAllKeysIncludeListScopes(): void
	{
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = SharedCacheVisibleModel::class;
				$this->cacheSharedPayload = true;
				parent::__construct();
			}

			public function all(Request $request): object
			{
				return (object)['success' => true, 'rows' => []];
			}
		};

		$policy = new RecordingModelPolicy($controller);
		$request = new Request();

		$GLOBALS['protoTestActor'] = (object)['id' => 42];
		$policy->all($request);
		$keyA = $policy->keys[0] ?? null;

		$GLOBALS['protoTestActor'] = (object)['id' => 99];
		$policy->all($request);
		$keyB = $policy->keys[1] ?? null;

		$GLOBALS['protoTestActor'] = (object)['id' => 42];
		$policy->all($request);
		$keyAAgain = $policy->keys[2] ?? null;

		$this->assertNotNull($keyA);
		$this->assertNotNull($keyB);
		$this->assertNotSame($keyA, $keyB);
		$this->assertSame($keyA, $keyAAgain);
	}

	/**
	 * invalidateGetKeys() must drop get:{id}, get:{id}:*, and slug/guid identities.
	 *
	 * @return void
	 */
	public function testInvalidateGetKeysDeletesIdIncludeAndSlugPatterns(): void
	{
		$previous = $_REQUEST['id'] ?? null;
		unset($_GET['id'], $_REQUEST['id']);

		try
		{
			$controller = new class extends Controller {};
			$policy = new class($controller) extends ModelPolicy
			{
				/**
				 * @var array<int, string>
				 */
				public array $patterns = [];

				protected function deleteKeysMatching(string $pattern): void
				{
					$this->patterns[] = $pattern;
				}

				public function exposeInvalidate(Request $request, mixed $id = null, ?object $item = null): void
				{
					$this->invalidateGetKeys($request, $id, $item);
				}

				public function exposePattern(string $method, mixed $params): string
				{
					return $this->createKeyPattern($method, $params);
				}
			};

			$policy->exposeInvalidate(
				new Request(),
				5,
				(object)['id' => 5, 'slug' => 'my-slug', 'guid' => 'abc']
			);

			$five = $policy->exposePattern('get', 5);
			$fifty = $policy->exposePattern('get', 50);
			// Class:*:get:5 must not match Class:*:get:50 (no trailing wildcard on the id).
			$this->assertStringEndsWith(':get:5', $five);
			$this->assertStringEndsWith(':get:50', $fifty);
			$this->assertFalse(str_ends_with($fifty, ':get:5'));

			$this->assertContains($five, $policy->patterns);
			$this->assertContains($five . ':*', $policy->patterns);
			$this->assertContains($policy->exposePattern('get', 'my-slug'), $policy->patterns);
			$this->assertContains($policy->exposePattern('get', 'my-slug') . ':*', $policy->patterns);
			$this->assertContains($policy->exposePattern('get', 'abc'), $policy->patterns);
			$this->assertContains($policy->exposePattern('get', 'abc') . ':*', $policy->patterns);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['id'], $_REQUEST['id']);
			}
			else
			{
				$_GET['id'] = $previous;
				$_REQUEST['id'] = $previous;
			}
		}
	}
}

/**
 * @package Proto\Tests\Unit\Cache
 */
final class RecordingModelPolicy extends ModelPolicy
{
	/**
	 * @var array<int, string>
	 */
	public array $keys = [];

	/**
	 * @param string $method
	 * @param mixed $params
	 * @return string
	 */
	protected function createKey(string $method, mixed $params): string
	{
		$key = parent::createKey($method, $params);
		$this->keys[] = $key;
		return $key;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getValue(string $key): mixed
	{
		return (object)['success' => true, 'rows' => []];
	}
}

/**
 * @package Proto\Tests\Unit\Cache
 */
final class SharedCacheVisibleModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'shared_cache_visible';

	/**
	 * @var array
	 */
	protected static array $fields = ['id'];

	/**
	 * @var array<int, class-string>
	 */
	protected static array $scopes = [VisibleScope::class];
}

namespace
{
	if (!function_exists('session'))
	{
		/**
		 * @return object
		 */
		function session(): object
		{
			return (object)['user' => $GLOBALS['protoTestActor'] ?? null];
		}
	}
}
