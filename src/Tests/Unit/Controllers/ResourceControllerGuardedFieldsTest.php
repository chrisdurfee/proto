<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ResourceControllerGuardedFieldsTest
 *
 * Model $guarded fields are dropped from client input on every write,
 * while server code in modifyAddItem()/modifyUpdateItem() can still set them.
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerGuardedFieldsTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param int|null $id
	 * @return Request
	 */
	private function request(?int $id = null): Request
	{
		$request = new Request();
		$request->setParams((object)($id !== null ? ['id' => (string)$id] : []));
		return $request;
	}

	/**
	 * @return void
	 */
	public function testAddDropsGuardedClientFields(): void
	{
		$controller = new GuardedController();
		$controller->payload = (object)['title' => 'x', 'verified' => 1, 'price' => 1];

		$controller->add($this->request());

		$this->assertSame('x', $controller->written->title ?? null);
		$this->assertObjectNotHasProperty('price', $controller->written);
	}

	/**
	 * @return void
	 */
	public function testServerCanStillSetGuardedFieldOnAdd(): void
	{
		$controller = new GuardedController();
		$controller->payload = (object)['title' => 'x', 'verified' => 1];

		$controller->add($this->request());

		$this->assertSame(0, $controller->written->verified ?? null);
	}

	/**
	 * @return void
	 */
	public function testUpdateDropsGuardedClientFields(): void
	{
		$controller = new GuardedController();
		$controller->payload = (object)['title' => 'y', 'verified' => 1, 'price' => 1];

		$controller->update($this->request(5));

		$this->assertSame('y', $controller->written->title ?? null);
		$this->assertObjectNotHasProperty('verified', $controller->written);
		$this->assertObjectNotHasProperty('price', $controller->written);
	}
}

/**
 * Controller that sets `verified` server-side on add.
 */
final class GuardedController extends ResourceController
{
	/** @var object|null */
	public ?object $payload = null;

	/** @var object|null */
	public ?object $written = null;

	public function __construct()
	{
		$this->model = GuardedModel::class;
		parent::__construct();
	}

	public function getRequestItem(Request $request): object
	{
		return $this->payload !== null ? clone $this->payload : (object)[];
	}

	protected function modifyAddItem(object &$data, Request $request): void
	{
		parent::modifyAddItem($data, $request);
		$data->verified = 0;
	}

	protected function addItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => 1]);
	}

	protected function updateItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => $data->id]);
	}
}

/**
 * Model with guarded verification and money fields.
 */
final class GuardedModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'guarded';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title', 'verified', 'price'];

	/**
	 * @var array
	 */
	protected static array $guarded = ['verified', 'price'];
}
