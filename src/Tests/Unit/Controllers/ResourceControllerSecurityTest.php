<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Models\Scopes\VisibleScope;
use Proto\Storage\Filter;
use Proto\Tests\Test;

/**
 * ResourceControllerSecurityTest
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerSecurityTest extends Test
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
	 * $scopeToUser overwrites a client-supplied userId.
	 *
	 * @return void
	 */
	public function testScopeToUserOverwritesClientUserId(): void
	{
		$GLOBALS['protoTestActor'] = (object)['id' => 7];
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = ScopeUserCaptureModel::class;
				$this->scopeToUser = true;
				parent::__construct();
			}

			public function exposeModifyAdd(object &$data, Request $request): void
			{
				$this->modifyAddItem($data, $request);
			}
		};

		$data = (object)['userId' => 999, 'title' => 'x'];
		$controller->exposeModifyAdd($data, new Request());
		$this->assertSame(7, $data->userId);
	}

	/**
	 * setup() goes through modifyAddItem so scoped userId is overwritten.
	 *
	 * @return void
	 */
	public function testSetupOverwritesScopedUserId(): void
	{
		$GLOBALS['protoTestActor'] = (object)['id' => 7];
		$controller = new class extends ResourceController
		{
			public ?object $setupData = null;

			public function __construct()
			{
				$this->model = ScopeUserCaptureModel::class;
				$this->scopeToUser = true;
				parent::__construct();
			}

			protected function setupItem(object $data): object
			{
				$this->setupData = $data;
				return (object)['success' => true, 'id' => 1];
			}
		};

		$previous = $_REQUEST['item'] ?? null;
		$_GET['title'] = 'hello';
		$_REQUEST['title'] = 'hello';
		$_GET['userId'] = '999';
		$_REQUEST['userId'] = '999';

		try
		{
			$controller->setup(new Request());
			$this->assertNotNull($controller->setupData);
			$this->assertSame(7, $controller->setupData->userId);
		}
		finally
		{
			unset($_GET['title'], $_REQUEST['title'], $_GET['userId'], $_REQUEST['userId']);
			if ($previous !== null)
			{
				$_REQUEST['item'] = $previous;
			}
		}
	}

	/**
	 * Request filter secrets are dropped; status is kept.
	 *
	 * @return void
	 */
	public function testGetFilterDropsSecretColumns(): void
	{
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = ScopeUserCaptureModel::class;
				parent::__construct();
			}

			public function exposeFilterColumns(): ?array
			{
				return $this->requestFilterColumns();
			}
		};

		$allowed = $controller->exposeFilterColumns();
		$this->assertNotNull($allowed);
		$this->assertContains('status', $allowed);
		$this->assertNotContains('email', $allowed);
		$this->assertNotContains('password', $allowed);

		$result = Filter::sanitizeRequestFilter(
			(object)[
				'email' => 'a@b.c',
				'password' => 'x',
				'status' => 'published'
			],
			$allowed
		);
		$values = (array)$result;
		$this->assertArrayHasKey('status', $values);
		$this->assertSame('published', $values['status']);
		$this->assertArrayNotHasKey('email', $values);
		$this->assertArrayNotHasKey('password', $values);
	}

	/**
	 * search() applies list scopes.
	 *
	 * @return void
	 */
	public function testSearchAppliesListScopes(): void
	{
		unset($GLOBALS['protoTestActor']);
		SearchCountCaptureModel::$lastFilter = null;
		SearchCountCaptureModel::$lastModifiers = null;

		$previous = $_REQUEST['search'] ?? null;
		$_GET['search'] = 'hello';
		$_REQUEST['search'] = 'hello';

		try
		{
			$controller = $this->scopedSearchController();
			$controller->search(new Request());
			$this->assertVisibleScopeClause(SearchCountCaptureModel::$lastFilter, 0);
			$this->assertSame('hello', SearchCountCaptureModel::$lastModifiers['search'] ?? null);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['search'], $_REQUEST['search']);
			}
			else
			{
				$_GET['search'] = $previous;
				$_REQUEST['search'] = $previous;
			}
		}
	}

	/**
	 * count() uses the request filter and list scopes, not an unfiltered count.
	 *
	 * @return void
	 */
	public function testCountUsesScopedRequestFilter(): void
	{
		unset($GLOBALS['protoTestActor']);
		SearchCountCaptureModel::$lastCountFilter = null;

		$request = new class extends Request
		{
			public function input(string $name, mixed $default = null): mixed
			{
				if ($name === 'filter')
				{
					return json_encode(['status' => 'published']);
				}

				return parent::input($name, $default);
			}
		};

		$controller = $this->scopedSearchController();
		$controller->count($request);
		$filter = SearchCountCaptureModel::$lastCountFilter;
		$this->assertIsArray($filter);
		$this->assertVisibleScopeClause($filter, 0);
		$this->assertTrue(
			isset($filter['status']) || isset($filter['p.status']),
			'Count filter must keep the request status predicate.'
		);
	}

	/**
	 * @return ResourceController
	 */
	private function scopedSearchController(): ResourceController
	{
		return new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = SearchCountCaptureModel::class;
				$this->scopes = [VisibleScope::class];
				parent::__construct();
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
final class ScopeUserCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'scope_user_capture';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'userId', 'title', 'status', 'email', 'password'];
}

/**
 * @package Proto\Tests\Unit\Controllers
 */
final class SearchCountCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'search_count_capture';

	/**
	 * @var string|null
	 */
	protected static ?string $alias = 'p';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title', 'status', 'privacy', 'userId'];

	/**
	 * @var mixed
	 */
	public static mixed $lastFilter = null;

	/**
	 * @var array|null
	 */
	public static ?array $lastModifiers = null;

	/**
	 * @var mixed
	 */
	public static mixed $lastCountFilter = null;

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
		self::$lastModifiers = $modifiers;
		return (object)['rows' => []];
	}

	/**
	 * @param mixed $filter
	 * @param array|null $modifiers
	 * @return object|false
	 */
	public static function count(mixed $filter = null, ?array $modifiers = null): object|false
	{
		self::$lastCountFilter = $filter;
		return (object)['count' => 0];
	}
}
