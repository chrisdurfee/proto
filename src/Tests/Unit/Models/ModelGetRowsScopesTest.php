<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Models\Scopes\Scope;
use Proto\Tests\Test;

/**
 * ModelGetRowsScopesTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class ModelGetRowsScopesTest extends Test
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
		parent::setUp();
		FlagScope::$calls = 0;
		unset($GLOBALS['protoTestActor']);
	}

	/**
	 * getRows() must invoke model $scopes even if storage throws.
	 *
	 * @return void
	 */
	public function testGetRowsAppliesModelScopes(): void
	{
		try
		{
			FlagScopedModel::getRows(null, 0, 1);
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(1, FlagScope::$calls);
	}

	/**
	 * all() and fetchWhere() go through getRows() and must apply scopes.
	 *
	 * @return void
	 */
	public function testAllAndFetchWhereApplyModelScopes(): void
	{
		try
		{
			FlagScopedModel::all(null, 0, 1);
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(1, FlagScope::$calls);

		FlagScope::$calls = 0;
		try
		{
			FlagScopedModel::fetchWhere(['id' => 1]);
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(1, FlagScope::$calls);
	}

	/**
	 * Direct get() / getWithoutJoins() must not apply list scopes.
	 *
	 * @return void
	 */
	public function testGetDoesNotApplyModelScopes(): void
	{
		try
		{
			FlagScopedModel::get(1);
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(0, FlagScope::$calls);

		try
		{
			FlagScopedModel::getWithoutJoins(1);
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(0, FlagScope::$calls);
	}

	/**
	 * ResourceController::all() must apply model $scopes once, not twice.
	 *
	 * @return void
	 */
	public function testControllerAllAppliesModelScopesOnce(): void
	{
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				$this->model = FlagScopedModel::class;
				parent::__construct();
			}
		};

		try
		{
			$controller->all(new Request());
		}
		catch (\Throwable)
		{
		}

		$this->assertSame(1, FlagScope::$calls);
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class FlagScope implements Scope
{
	/**
	 * @var int
	 */
	public static int $calls = 0;

	/**
	 * @param mixed $filter
	 * @param object|null $actor
	 * @return mixed
	 */
	public function apply(mixed $filter, ?object $actor = null): mixed
	{
		self::$calls++;
		return $filter;
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class FlagScopedModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'flag_scoped_rows';

	/**
	 * @var array
	 */
	protected static array $fields = ['id'];

	/**
	 * @var array<int, class-string>
	 */
	protected static array $scopes = [FlagScope::class];
}
