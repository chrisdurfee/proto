<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ResourceControllerUpsertHookTest
 *
 * PUT (setup) is authorized like PATCH and overwrites an existing row,
 * so it must run modifyUpdateItem() there. It used to run
 * modifyAddItem(), which skipped every restriction an app put in its
 * update hook (and the immutable-field strip).
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerUpsertHookTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param int $id
	 * @return Request
	 */
	private function request(int $id): Request
	{
		$request = new Request();
		$request->setParams((object)['id' => (string)$id]);
		return $request;
	}

	/**
	 * @return void
	 */
	public function testSetupOnExistingRowRunsUpdateHook(): void
	{
		$controller = new UpsertHookController();
		$controller->payload = (object)['title' => 'x', 'ownerId' => 99];

		$controller->setup($this->request(5));

		$this->assertSame(['update'], $controller->hooks);
		$this->assertObjectNotHasProperty('ownerId', $controller->written);
		$this->assertSame(5, $controller->written->id ?? null);
	}

	/**
	 * @return void
	 */
	public function testSetupOnNewRowRunsAddHook(): void
	{
		$controller = new UpsertHookController();
		$controller->payload = (object)['title' => 'x'];

		$controller->setup($this->request(7));

		$this->assertSame(['add'], $controller->hooks);
	}

	/**
	 * @return void
	 */
	public function testMergeOnExistingRowRunsUpdateHook(): void
	{
		$controller = new UpsertHookController();
		$controller->payload = (object)['title' => 'x', 'ownerId' => 99];

		$controller->merge($this->request(5));

		$this->assertSame(['update'], $controller->hooks);
		$this->assertObjectNotHasProperty('ownerId', $controller->written);
	}
}

/**
 * Controller over an in-memory row set (row 5 exists) that records which
 * write hook ran.
 */
final class UpsertHookController extends ResourceController
{
	/** @var object|null */
	public ?object $payload = null;

	/** @var object|null */
	public ?object $written = null;

	/** @var string[] */
	public array $hooks = [];

	public function __construct()
	{
		$this->model = UpsertHookModel::class;
		parent::__construct();
	}

	public function getRequestItem(Request $request): object
	{
		return $this->payload !== null ? clone $this->payload : (object)[];
	}

	protected function findRouteBoundRow(array $filter): ?object
	{
		return (int)($filter['id'] ?? 0) === 5 ? (object)['id' => 5, 'ownerId' => 1] : null;
	}

	protected function modifyAddItem(object &$data, Request $request): void
	{
		$this->hooks[] = 'add';
		parent::modifyAddItem($data, $request);
	}

	protected function modifyUpdateItem(object &$data, Request $request): void
	{
		$this->hooks[] = 'update';
		parent::modifyUpdateItem($data, $request);
	}

	protected function setupItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => $data->id]);
	}

	protected function mergeItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => $data->id]);
	}
}

/**
 * Model whose ownerId may not change after creation.
 */
final class UpsertHookModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'upsert_hook';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'ownerId', 'title'];

	/**
	 * @var array
	 */
	protected static array $immutableFields = ['ownerId'];
}
