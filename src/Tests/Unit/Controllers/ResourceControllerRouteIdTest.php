<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Auth\Policies\Policy;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ResourceControllerRouteIdTest
 *
 * Regression tests for the route-id binding. Policies authorize the route
 * `:id`, so controllers must act on that same row: a query, form or body
 * `id` must never redirect a read or write to another row.
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerRouteIdTest extends Test
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
		unset($_GET['id'], $_POST['id'], $_REQUEST['id']);
		parent::tearDown();
	}

	/**
	 * Builds a request whose route has the given `:id`.
	 *
	 * @param int|null $routeId
	 * @return Request
	 */
	private function routeRequest(?int $routeId): Request
	{
		$request = new Request();
		$request->setParams((object)($routeId !== null ? ['id' => (string)$routeId] : []));
		return $request;
	}

	/**
	 * Sets a query-string `id`, as an attacker would append `?id=`.
	 *
	 * @param int $id
	 * @return void
	 */
	private function setQueryId(int $id): void
	{
		$_GET['id'] = (string)$id;
		$_REQUEST['id'] = (string)$id;
	}

	/**
	 * @return RouteIdCaptureController
	 */
	private function controller(): RouteIdCaptureController
	{
		return new RouteIdCaptureController();
	}

	/**
	 * The route id wins over a query id.
	 *
	 * @return void
	 */
	public function testResourceIdPrefersRouteIdOverQueryId(): void
	{
		$this->setQueryId(9);
		$this->assertSame(5, $this->controller()->exposeResourceId($this->routeRequest(5)));
	}

	/**
	 * Routes without `:id` still read the request id.
	 *
	 * @return void
	 */
	public function testResourceIdFallsBackToQueryIdWithoutRouteId(): void
	{
		$this->setQueryId(9);
		$this->assertSame(9, $this->controller()->exposeResourceId($this->routeRequest(null)));
	}

	/**
	 * The policy resolves the same id as the controller.
	 *
	 * @return void
	 */
	public function testPolicyResourceIdMatchesController(): void
	{
		$this->setQueryId(9);
		$policy = new RouteIdCapturePolicy();
		$this->assertSame(5, $policy->exposeResourceId($this->routeRequest(5)));
		$this->assertSame(9, $policy->exposeResourceId($this->routeRequest(null)));
	}

	/**
	 * Update rejects a body id that differs from the route id.
	 *
	 * @return void
	 */
	public function testUpdateRejectsMismatchedBodyId(): void
	{
		$controller = $this->controller();
		$controller->payload = (object)['id' => 9, 'title' => 'x'];

		$response = $controller->update($this->routeRequest(5));

		$this->assertFalse($response->success);
		$this->assertNull($controller->written);
	}

	/**
	 * Update without a body id acts on the route id, even with `?id=`.
	 *
	 * @return void
	 */
	public function testUpdateUsesRouteIdIgnoringQueryId(): void
	{
		$this->setQueryId(9);
		$controller = $this->controller();
		$controller->payload = (object)['title' => 'x'];

		$controller->update($this->routeRequest(5));

		$this->assertSame(5, $controller->written->id ?? null);
	}

	/**
	 * Update with a matching body id is allowed.
	 *
	 * @return void
	 */
	public function testUpdateAllowsMatchingBodyId(): void
	{
		$controller = $this->controller();
		$controller->payload = (object)['id' => '5', 'title' => 'x'];

		$controller->update($this->routeRequest(5));

		$this->assertSame(5, $controller->written->id ?? null);
	}

	/**
	 * Setup (PUT) rejects a body id that differs from the route id.
	 *
	 * @return void
	 */
	public function testSetupRejectsMismatchedBodyId(): void
	{
		$controller = $this->controller();
		$controller->payload = (object)['id' => 9, 'title' => 'x'];

		$response = $controller->setup($this->routeRequest(5));

		$this->assertFalse($response->success);
		$this->assertNull($controller->written);
	}

	/**
	 * Merge rejects a body id that differs from the route id.
	 *
	 * @return void
	 */
	public function testMergeRejectsMismatchedBodyId(): void
	{
		$controller = $this->controller();
		$controller->payload = (object)['id' => 9, 'title' => 'x'];

		$response = $controller->merge($this->routeRequest(5));

		$this->assertFalse($response->success);
		$this->assertNull($controller->written);
	}

	/**
	 * Delete acts on the route id, even with `?id=`.
	 *
	 * @return void
	 */
	public function testDeleteUsesRouteIdIgnoringQueryId(): void
	{
		$this->setQueryId(9);
		$controller = $this->controller();

		$controller->delete($this->routeRequest(5));

		$this->assertSame(5, $controller->deleted->id ?? null);
	}
}

/**
 * Controller that records the row a write would act on.
 */
final class RouteIdCaptureController extends ResourceController
{
	/**
	 * @var object|null
	 */
	public ?object $payload = null;

	/**
	 * @var object|null
	 */
	public ?object $written = null;

	/**
	 * @var object|null
	 */
	public ?object $deleted = null;

	public function __construct()
	{
		$this->model = RouteIdCaptureModel::class;
		parent::__construct();
	}

	public function getRequestItem(Request $request): object
	{
		return $this->payload !== null ? clone $this->payload : (object)[];
	}

	public function exposeResourceId(Request $request): ?int
	{
		return $this->getResourceId($request);
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

	protected function mergeItem(object $data): object
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
 * Policy exposing the base id resolution.
 */
final class RouteIdCapturePolicy extends Policy
{
	public function exposeResourceId(Request $request): ?int
	{
		return $this->getResourceId($request);
	}
}

/**
 * Minimal model for the route-id tests.
 */
final class RouteIdCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'route_id_capture';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'title', 'userId'];
}
