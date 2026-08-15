<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
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
