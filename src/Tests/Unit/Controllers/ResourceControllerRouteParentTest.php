<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ResourceControllerRouteParentTest
 *
 * Nested routes (`/vehicle/:vehicleId/option/:id`) usually authorize the
 * parent only. With $routeParams declared, a child row from another
 * parent must not be readable or writable through this parent's URL.
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerRouteParentTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param int $vehicleId
	 * @param int|null $id
	 * @return Request
	 */
	private function request(int $vehicleId, ?int $id): Request
	{
		$params = ['vehicleId' => (string)$vehicleId];
		if ($id !== null)
		{
			$params['id'] = (string)$id;
		}

		$request = new Request();
		$request->setParams((object)$params);
		return $request;
	}

	/**
	 * get() looks the child up within the route parent.
	 *
	 * @return void
	 */
	public function testGetLookupIncludesRouteParent(): void
	{
		$controller = new RouteParentController();
		$controller->get($this->request(1, 6));

		$this->assertSame(['id' => 6, 'vehicleId' => 1], $controller->lastLookup);
	}

	/**
	 * Update of a child that belongs to another parent is refused.
	 *
	 * @return void
	 */
	public function testUpdateRefusesChildOfAnotherParent(): void
	{
		$controller = new RouteParentController();
		$controller->payload = (object)['title' => 'x'];

		$response = $controller->update($this->request(1, 6));

		$this->assertFalse($response->success);
		$this->assertNull($controller->written);
	}

	/**
	 * A write cannot move a child to another parent through the body.
	 *
	 * @return void
	 */
	public function testUpdatePinsRouteParent(): void
	{
		$controller = new RouteParentController();
		$controller->payload = (object)['title' => 'x', 'vehicleId' => 2];

		$controller->update($this->request(1, 5));

		$this->assertSame(1, $controller->written->vehicleId ?? null);
	}

	/**
	 * Delete of a child that belongs to another parent is refused.
	 *
	 * @return void
	 */
	public function testDeleteRefusesChildOfAnotherParent(): void
	{
		$controller = new RouteParentController();

		$response = $controller->delete($this->request(1, 6));

		$this->assertFalse($response->success);
		$this->assertNull($controller->deleted);
	}

	/**
	 * Delete of an own child is allowed.
	 *
	 * @return void
	 */
	public function testDeleteAllowsOwnChild(): void
	{
		$controller = new RouteParentController();

		$controller->delete($this->request(1, 5));

		$this->assertSame(5, $controller->deleted->id ?? null);
	}

	/**
	 * A route param that is not a model field (it maps to another
	 * column name) is not used for binding, so the lookup stays valid.
	 *
	 * @return void
	 */
	public function testRouteParamThatIsNotAFieldIsIgnored(): void
	{
		$controller = new RouteParentController();
		$controller->useMismatchedParam();

		$request = new Request();
		$request->setParams((object)['ticketParam' => '1', 'id' => '6']);
		$controller->get($request);

		$this->assertSame(['id' => 6], $controller->lastLookup);
	}

	/**
	 * Setup (upsert) may create a new id but not take over another parent's row.
	 *
	 * @return void
	 */
	public function testSetupAllowsNewRowButNotForeignRow(): void
	{
		$controller = new RouteParentController();
		$controller->payload = (object)['title' => 'x'];
		$controller->setup($this->request(1, 7));
		$this->assertSame(7, $controller->written->id ?? null);

		$controller = new RouteParentController();
		$controller->payload = (object)['title' => 'x'];
		$response = $controller->setup($this->request(1, 6));
		$this->assertFalse($response->success);
		$this->assertNull($controller->written);
	}
}

/**
 * Nested controller backed by an in-memory row set:
 * row 5 belongs to vehicle 1, row 6 to vehicle 2.
 */
final class RouteParentController extends ResourceController
{
	/** @var object|null */
	public ?object $payload = null;

	/** @var object|null */
	public ?object $written = null;

	/** @var object|null */
	public ?object $deleted = null;

	/** @var array|null */
	public ?array $lastLookup = null;

	/** @var array<int, array<string, int>> */
	private array $rows = [
		['id' => 5, 'vehicleId' => 1],
		['id' => 6, 'vehicleId' => 2]
	];

	public function __construct()
	{
		$this->model = RouteParentModel::class;
		$this->routeParams = ['vehicleId' => true];
		parent::__construct();
	}

	public function getRequestItem(Request $request): object
	{
		return $this->payload !== null ? clone $this->payload : (object)[];
	}

	public function useMismatchedParam(): void
	{
		$this->routeParams = ['ticketParam' => true];
	}

	protected function findRouteBoundRow(array $filter): ?object
	{
		foreach ($this->rows as $row)
		{
			if (array_intersect_assoc($filter, $row) === $filter)
			{
				return (object)$row;
			}
		}

		return null;
	}

	protected function firstScoped(Request $request, array $lookup): ?object
	{
		$this->lastLookup = $lookup;
		return null;
	}

	protected function updateItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => $data->id]);
	}

	protected function setupItem(object $data): object
	{
		$this->written = $data;
		return $this->response(['id' => $data->id]);
	}

	protected function deleteItem(object $data): object
	{
		$this->deleted = $data;
		return $this->response(['id' => $data->id]);
	}
}

/**
 * Minimal child model.
 */
final class RouteParentModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'route_parent';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'vehicleId', 'title'];
}
