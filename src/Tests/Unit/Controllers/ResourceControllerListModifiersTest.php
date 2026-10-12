<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ResourceControllerListModifiersTest
 *
 * Client `orderBy`, `groupBy`, and `dates` modifiers may only name the
 * model's sortable fields. Sorting or range-filtering on a secret
 * column is a comparison oracle (and used to echo the raw value back
 * in `lastCursor`), so those keys must be dropped before they reach
 * the storage layer. Paging values are clamped.
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerListModifiersTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var array
	 */
	private array $savedRequest = [];

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->savedRequest = $_REQUEST;
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		$_REQUEST = $this->savedRequest;
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSortableFieldsExcludeSecretsButKeepAuditColumns(): void
	{
		$fields = ListModifiersModel::sortableFields();

		$this->assertContains('id', $fields);
		$this->assertContains('name', $fields);
		$this->assertContains('createdAt', $fields);
		$this->assertNotContains('password', $fields);
		$this->assertNotContains('resetToken', $fields);
		$this->assertNotContains('email', $fields);
		$this->assertNotContains('internalNote', $fields, 'Blacklisted fields are never sortable.');
	}

	/**
	 * @return void
	 */
	public function testOrderByDropsSensitiveColumns(): void
	{
		$_REQUEST = [
			'orderBy' => json_encode(['password' => 'asc', 'name' => 'desc', 'reset_token' => 'asc'])
		];

		$inputs = $this->controller()->getAllInputs(new Request());

		$this->assertEquals((object)['name' => 'desc'], $inputs->orderBy);
		$this->assertEquals((object)['name' => 'desc'], $inputs->modifiers['orderBy']);
	}

	/**
	 * @return void
	 */
	public function testOrderByOfOnlySensitiveColumnsBecomesNull(): void
	{
		$_REQUEST = ['orderBy' => json_encode(['password' => 'asc'])];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertNull($inputs->orderBy);
	}

	/**
	 * @return void
	 */
	public function testDatesOnSensitiveFieldIsDropped(): void
	{
		$_REQUEST = [
			'dates' => json_encode(['field' => 'resetToken', 'start' => '2026-01-01', 'end' => '2026-02-01'])
		];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertNull($inputs->dates);
	}

	/**
	 * @return void
	 */
	public function testDatesWithNonTimestampBoundsAreDropped(): void
	{
		$_REQUEST = [
			'dates' => json_encode(['field' => 'createdAt', 'start' => 'a:', 'end' => 'b:'])
		];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertNull($inputs->dates);
	}

	/**
	 * @return void
	 */
	public function testValidDatesPassThrough(): void
	{
		$_REQUEST = [
			'dates' => json_encode(['field' => 'createdAt', 'start' => '2026-01-01', 'end' => '2026-02-01 12:00:00'])
		];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertEquals('createdAt', $inputs->dates->field);
		$this->assertEquals('2026-01-01', $inputs->dates->start);
		$this->assertEquals('2026-02-01 12:00:00', $inputs->dates->end);
	}

	/**
	 * ISO timestamps from JavaScript (Z or offset) are accepted and
	 * converted to the app timezone.
	 *
	 * @return void
	 */
	public function testIsoTimestampsAreNormalized(): void
	{
		$saved = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$_REQUEST = [
			'dates' => json_encode(['field' => 'createdAt', 'start' => '2026-01-01T05:30:00.000Z', 'end' => '2026-01-02T00:00:00+02:00'])
		];

		try
		{
			$inputs = $this->controller()->getAllInputs(new Request());
		}
		finally
		{
			date_default_timezone_set($saved);
		}

		$this->assertSame('2026-01-01 05:30:00', $inputs->dates->start);
		$this->assertSame('2026-01-01 22:00:00', $inputs->dates->end);
	}

	/**
	 * @return void
	 */
	public function testGroupByDropsSensitiveColumns(): void
	{
		$_REQUEST = ['groupBy' => json_encode(['name', 'email'])];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertEquals(['name'], $inputs->groupBy);
	}

	/**
	 * @return void
	 */
	public function testPagingIsClamped(): void
	{
		$_REQUEST = ['limit' => '-5', 'offset' => '-20'];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertSame(1, $inputs->limit);
		$this->assertSame(0, $inputs->offset);

		$_REQUEST = ['limit' => '999999'];
		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertSame(1000, $inputs->limit);
	}

	/**
	 * @return void
	 */
	public function testNonScalarCursorIsIgnored(): void
	{
		$_REQUEST = ['lastCursor' => ['x' => 1]];

		$inputs = $this->controller()->getAllInputs(new Request());
		$this->assertNull($inputs->lastCursor);
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
				$this->model = ListModifiersModel::class;
				parent::__construct();
			}
		};
	}
}

/**
 * @package Proto\Tests\Unit\Controllers
 */
final class ListModifiersModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'list_modifiers_test';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'name', 'email', 'password', 'resetToken', 'internalNote', 'createdAt'];

	/**
	 * @var array
	 */
	protected static array $fieldsBlacklist = ['internalNote'];
}
