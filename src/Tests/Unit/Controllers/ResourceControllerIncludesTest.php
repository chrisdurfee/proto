<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Models\Scopes\VisibleScope;
use Proto\Tests\Test;

/**
 * ResourceControllerIncludesTest
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerIncludesTest extends Test
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
	 * @return ResourceController
	 */
	private function controller(): ResourceController
	{
		return new class extends ResourceController
		{
			public function __construct()
			{
				$this->allowedIncludes = ['author', 'stats'];
				$this->defaultIncludes = ['author'];
				parent::__construct();
			}
		};
	}

	/**
	 * @return void
	 */
	public function testRequestedIncludesAllowlistAndDefaults(): void
	{
		$previous = $_REQUEST['include'] ?? null;
		$_GET['include'] = 'author,stats,secret';
		$_REQUEST['include'] = 'author,stats,secret';

		try
		{
			$includes = $this->controller()->requestedIncludes(new Request());
			$this->assertEquals(['author', 'stats'], $includes);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['include'], $_REQUEST['include']);
			}
			else
			{
				$_GET['include'] = $previous;
				$_REQUEST['include'] = $previous;
			}
		}
	}

	/**
	 * Unknown include names are dropped when nothing is allowlisted.
	 *
	 * @return void
	 */
	public function testEmptyAllowlistIgnoresQueryIncludes(): void
	{
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				parent::__construct();
			}
		};

		$previous = $_REQUEST['include'] ?? null;
		$_GET['include'] = 'author';
		$_REQUEST['include'] = 'author';

		try
		{
			$this->assertSame([], $controller->requestedIncludes(new Request()));
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['include'], $_REQUEST['include']);
			}
			else
			{
				$_GET['include'] = $previous;
				$_REQUEST['include'] = $previous;
			}
		}
	}

	/**
	 * get() lookups must be alias-qualified so joins do not make `id` ambiguous.
	 *
	 * @return void
	 */
	public function testFirstScopedQualifiesIdLookup(): void
	{
		FirstScopedCaptureModel::$lastFilter = null;

		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = FirstScopedCaptureModel::class;
				parent::__construct();
			}

			public function exposeFirstScoped(Request $request, array $lookup): ?object
			{
				return $this->firstScoped($request, $lookup);
			}
		};

		$controller->exposeFirstScoped(new Request(), ['id' => 5]);
		$this->assertIsArray(FirstScopedCaptureModel::$lastFilter);
		$this->assertArrayHasKey('p.id', FirstScopedCaptureModel::$lastFilter);
		$this->assertEquals(5, FirstScopedCaptureModel::$lastFilter['p.id']);
	}

	/**
	 * firstScoped() must apply list scopes so drafts stay hidden on get().
	 *
	 * @return void
	 */
	public function testFirstScopedAppliesVisibleScopeForAnonymous(): void
	{
		unset($GLOBALS['protoTestActor']);
		ScopedGetCaptureModel::$lastFilter = null;
		ScopedGetCaptureModel::$rowsToReturn = [];

		$controller = $this->visibleScopeController();
		$found = $controller->exposeFirstScoped(new Request(), ['id' => 5]);

		$this->assertNull($found);
		$this->assertVisibleScopeClause(ScopedGetCaptureModel::$lastFilter, 0);
		$this->assertArrayHasKey('p.id', ScopedGetCaptureModel::$lastFilter);
		$this->assertEquals(5, ScopedGetCaptureModel::$lastFilter['p.id']);
	}

	/**
	 * Owner get() still includes the owner id in the scoped filter.
	 *
	 * @return void
	 */
	public function testFirstScopedAppliesVisibleScopeForOwner(): void
	{
		$GLOBALS['protoTestActor'] = (object)['id' => 42];
		ScopedGetCaptureModel::$lastFilter = null;
		$row = (object)['id' => 5, 'title' => 'mine'];
		ScopedGetCaptureModel::$rowsToReturn = [$row];

		$controller = $this->visibleScopeController();
		$found = $controller->exposeFirstScoped(new Request(), ['id' => 5]);

		$this->assertSame($row, $found);
		$this->assertVisibleScopeClause(ScopedGetCaptureModel::$lastFilter, 42);
	}

	/**
	 * resolveGetModel() is null when scopes hide the row.
	 *
	 * @return void
	 */
	public function testResolveGetModelIsNullWhenScopeHidesRow(): void
	{
		unset($GLOBALS['protoTestActor']);
		ScopedGetCaptureModel::$lastFilter = null;
		ScopedGetCaptureModel::$rowsToReturn = [];

		$previous = $_REQUEST['id'] ?? null;
		$_GET['id'] = '5';
		$_REQUEST['id'] = '5';

		try
		{
			$controller = $this->visibleScopeController();
			$this->assertNull($controller->exposeResolveGetModel(new Request()));
			$this->assertVisibleScopeClause(ScopedGetCaptureModel::$lastFilter, 0);
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
	 * @return ResourceController
	 */
	private function visibleScopeController(): ResourceController
	{
		return new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = ScopedGetCaptureModel::class;
				$this->scopes = [VisibleScope::class];
				parent::__construct();
			}

			public function exposeFirstScoped(Request $request, array $lookup): ?object
			{
				return $this->firstScoped($request, $lookup);
			}

			public function exposeResolveGetModel(Request $request): ?object
			{
				return $this->resolveGetModel($request);
			}
		};
	}

	/**
	 * @param mixed $filter
	 * @param int $userId
	 * @return void
	 */
	private function assertVisibleScopeClause(mixed $filter, int $userId): void
	{
		$this->assertIsArray($filter);
		$found = false;
		foreach ($filter as $entry)
		{
			if (!is_array($entry) || !isset($entry[0], $entry[1]) || !is_array($entry[1]))
			{
				continue;
			}

			if (str_contains((string)$entry[0], 'privacy') && $entry[1] === [$userId, 'public', 'published'])
			{
				$found = true;
				break;
			}
		}

		$this->assertTrue($found, 'Filter must include the VisibleScope owner-or-public clause.');
	}
}

/**
 * @package Proto\Tests\Unit\Controllers
 */
final class FirstScopedCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'first_scoped_posts';

	/**
	 * @var string|null
	 */
	protected static ?string $alias = 'p';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title'];

	/**
	 * @var mixed
	 */
	public static mixed $lastFilter = null;

	/**
	 * @param mixed $filter
	 * @param int|null $offset
	 * @param int|null $limit
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function all(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object|false
	{
		self::$lastFilter = $filter;
		return (object)['rows' => []];
	}
}

/**
 * @package Proto\Tests\Unit\Controllers
 */
final class ScopedGetCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'scoped_get_posts';

	/**
	 * @var string|null
	 */
	protected static ?string $alias = 'p';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title'];

	/**
	 * @var mixed
	 */
	public static mixed $lastFilter = null;

	/**
	 * @var array<int, object>
	 */
	public static array $rowsToReturn = [];

	/**
	 * @param mixed $filter
	 * @param int|null $offset
	 * @param int|null $limit
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function all(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object|false
	{
		self::$lastFilter = $filter;
		return (object)['rows' => self::$rowsToReturn];
	}
}
