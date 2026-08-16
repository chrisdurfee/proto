<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Request as HttpRequest;
use Proto\Http\Router\Router;
use Proto\Http\Router\Uri;
use Proto\Tests\Test;

/**
 * Marker middleware used to detect stack leaks across deferred routes.
 */
final class SnapshotMarkerMiddleware
{
	/**
	 * @param mixed $request
	 * @param callable $next
	 * @return mixed
	 */
	public function handle(mixed $request, callable $next): mixed
	{
		return $next($request);
	}
}

/**
 * MiddlewareSnapshotTest
 *
 * Deferred api.php flush must use the middleware snapshotted when the
 * route was registered, not the router stack at flush time.
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class MiddlewareSnapshotTest extends Test
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
		HttpRequest::reset();
		unset($GLOBALS['router']);
		parent::tearDown();
	}

	/**
	 * router()->middleware() after an earlier group must not attach to
	 * the already-registered csrf-style route when activation is deferred.
	 *
	 * @return void
	 */
	public function testDeferredFlushDoesNotLeakLaterRouterMiddleware(): void
	{
		$router = $this->deferredRouter('/auth/csrf-token', 'GET');
		$GLOBALS['router'] = $router;

		$router->group('auth', function(Router $router): void
		{
			$router->get('csrf-token', static fn() => (object)['ok' => true]);
		});

		$router->middleware([SnapshotMarkerMiddleware::class]);
		$router->group('auth', function(Router $router): void
		{
			$router->post('login', static fn() => (object)['ok' => true]);
		});

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/auth/csrf-token', $pending->uri());
		$this->assertNotContains(SnapshotMarkerMiddleware::class, $this->routeMiddleware($pending));
		$router->endDeferral();
	}

	/**
	 * Group middleware is snapshotted onto routes registered inside the group.
	 *
	 * @return void
	 */
	public function testGroupMiddlewareIsSnapshottedOntoGroupRoutes(): void
	{
		$router = $this->deferredRouter('/auth/login', 'POST');
		$GLOBALS['router'] = $router;

		$router->group('auth', function(Router $router): void
		{
			$router->get('csrf-token', static fn() => (object)['ok' => true]);
		});

		$router->group('auth', function(Router $router): void
		{
			$router->withoutMutationMiddleware()->post('login', static fn() => (object)['ok' => true]);
		}, [SnapshotMarkerMiddleware::class]);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/auth/login', $pending->uri());
		$this->assertContains(SnapshotMarkerMiddleware::class, $this->routeMiddleware($pending));
		$router->endDeferral();
	}

	/**
	 * @param string $path
	 * @param string $method
	 * @return Router
	 */
	private function deferredRouter(string $path, string $method = 'GET'): Router
	{
		HttpRequest::reset();
		$http = new \ReflectionClass(HttpRequest::class);
		$http->getProperty('httpMethod')->setValue(null, $method);
		$http->getProperty('currentPath')->setValue(null, $path);

		$router = new Router('');
		$router->deferActivation();
		return $router;
	}

	/**
	 * @param Uri $route
	 * @return array<int, mixed>
	 */
	private function routeMiddleware(Uri $route): array
	{
		$ref = new \ReflectionProperty(Uri::class, 'middleware');
		$stack = $ref->getValue($route);
		return is_array($stack) ? $stack : [];
	}
}
