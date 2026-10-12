<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Request as HttpRequest;
use Proto\Http\Router\Request;
use Proto\Http\Router\Router;
use Proto\Tests\Test;

/**
 * RouterDispatchTest
 *
 * Router route selection under deferred activation: a matching route is
 * held as pendingRoute() instead of activated (activation exits), so
 * selection, precedence and the no-match case can be asserted in-process.
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class RouterDispatchTest extends Test
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
		HttpRequest::reset();
		parent::tearDown();
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param string $basePath
	 * @return Router
	 */
	private function router(string $method, string $path, string $basePath = ''): Router
	{
		HttpRequest::reset();
		$http = new \ReflectionClass(HttpRequest::class);
		$http->getProperty('httpMethod')->setValue(null, $method);
		$http->getProperty('currentPath')->setValue(null, $path);

		$router = new Router($basePath);
		$router->deferActivation();
		return $router;
	}

	/**
	 * @param string $name
	 * @return \Closure
	 */
	private function handler(string $name): \Closure
	{
		return static fn(Request $req) => (object)['handler' => $name, 'params' => $req->params()];
	}

	/**
	 * Runs the selected route's handler, or returns null for no match.
	 *
	 * @param Router $router
	 * @return object|null
	 */
	private function selected(Router $router): ?object
	{
		$route = $router->pendingRoute();
		$result = $route?->activate(new Request());
		$router->endDeferral();
		return $result;
	}

	/**
	 * @return void
	 */
	public function testNoMatchingRouteSelectsNothing(): void
	{
		$router = $this->router('GET', '/missing');
		$router->get('user/:id', $this->handler('user'));
		$router->get('health', $this->handler('health'));

		$this->assertNull($router->pendingRoute());
		$router->endDeferral();
	}

	/**
	 * @return void
	 */
	public function testParamsReachTheHandler(): void
	{
		$router = $this->router('GET', '/user/12/post/3');
		$router->get('user/:userId/post/:id', $this->handler('post'));

		$result = $this->selected($router);
		$this->assertSame('post', $result->handler);
		$this->assertEquals((object)['userId' => '12', 'id' => '3'], $result->params);
	}

	/**
	 * @return void
	 */
	public function testMethodSelectsAmongSamePath(): void
	{
		$router = $this->router('DELETE', '/user/1');
		$router->get('user/:id', $this->handler('get'));
		$router->post('user/:id', $this->handler('post'));
		$router->put('user/:id', $this->handler('put'));
		$router->patch('user/:id', $this->handler('patch'));
		$router->delete('user/:id', $this->handler('delete'));

		$this->assertSame('delete', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testWrongMethodSelectsNothing(): void
	{
		$router = $this->router('POST', '/user/1');
		$router->get('user/:id', $this->handler('get'));

		$this->assertNull($this->selected($router));
	}

	/**
	 * @return void
	 */
	public function testAllRouteMatchesAnyMethod(): void
	{
		$router = $this->router('PATCH', '/user/1');
		$router->all('user/:id', $this->handler('all'));

		$this->assertSame('all', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testLiteralBeatsParamRegardlessOfOrder(): void
	{
		$first = $this->router('GET', '/user/me/posts');
		$first->get('user/:id/posts', $this->handler('param'));
		$first->get('user/me/posts', $this->handler('literal'));
		$this->assertSame('literal', $this->selected($first)->handler);

		$second = $this->router('GET', '/user/me/posts');
		$second->get('user/me/posts', $this->handler('literal'));
		$second->get('user/:id/posts', $this->handler('param'));
		$this->assertSame('literal', $this->selected($second)->handler);
	}

	/**
	 * @return void
	 */
	public function testParamBeatsWildcard(): void
	{
		$router = $this->router('GET', '/files/report');
		$router->get('files/*', $this->handler('wildcard'));
		$router->get('files/:name', $this->handler('param'));

		$this->assertSame('param', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testEqualSpecificityKeepsFirstRegistered(): void
	{
		$router = $this->router('GET', '/user/5');
		$router->get('user/:id', $this->handler('first'));
		$router->get('user/:userId', $this->handler('second'));

		$this->assertSame('first', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testTrailingSlashAndQueryStringAreIgnored(): void
	{
		$router = $this->router('GET', '/user/5/?tab=posts');
		$router->get('user/:id', $this->handler('user'));

		$this->assertEquals((object)['id' => '5'], $this->selected($router)->params);
	}

	/**
	 * @return void
	 */
	public function testBasePathPrefixesRoutes(): void
	{
		$router = $this->router('GET', '/api/user/5', '/api/');
		$router->get('user/:id', $this->handler('user'));

		$this->assertStringEndsWith('/api/user/:id', $router->pendingRoute()->uri());
		$this->assertSame('user', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testRouteOutsideBasePathDoesNotMatch(): void
	{
		$router = $this->router('GET', '/user/5', '/api');
		$router->get('user/:id', $this->handler('user'));

		$this->assertNull($this->selected($router));
	}

	/**
	 * @return void
	 */
	public function testGroupPrefixesAndRestoresBasePath(): void
	{
		$router = $this->router('GET', '/api/v2/user/5', '/api');
		$router->group('v2', function(Router $r): void
		{
			$r->get('user/:id', $this->handler('grouped'));
		});
		$router->get('v2/user/:id', $this->handler('ungrouped'));

		$route = $router->pendingRoute();
		$this->assertSame('/api/v2/user/:id', $route->uri());
		$this->assertSame('grouped', $this->selected($router)->handler);
	}

	/**
	 * @return void
	 */
	public function testResourceOptionalIdMatchesCollectionAndItem(): void
	{
		$collection = $this->router('GET', '/post');
		$collection->resource('post', \stdClass::class);
		$this->assertStringEndsWith('/post/:id?', $collection->pendingRoute()?->uri() ?? '');
		$collection->endDeferral();

		$item = $this->router('DELETE', '/post/4');
		$item->resource('post', \stdClass::class);
		$this->assertNotNull($item->pendingRoute());
		$item->endDeferral();
	}

	/**
	 * @return void
	 */
	public function testEndDeferralClearsPendingRoute(): void
	{
		$router = $this->router('GET', '/user/1');
		$router->get('user/:id', $this->handler('user'));
		$this->assertTrue($router->isDeferred());

		$router->endDeferral();
		$this->assertFalse($router->isDeferred());
		$this->assertNull($router->pendingRoute());
	}

	/**
	 * Nested deferral keeps the pending match until the outer frame ends.
	 *
	 * @return void
	 */
	public function testNestedDeferralKeepsPendingUntilOutermostEnds(): void
	{
		$router = $this->router('GET', '/user/1');
		$router->deferActivation();
		$router->get('user/:id', $this->handler('user'));

		$router->endDeferral();
		$this->assertNotNull($router->pendingRoute());
		$router->endDeferral();
		$this->assertNull($router->pendingRoute());

		$router->endDeferral();
		$this->assertFalse($router->isDeferred(), 'Extra endDeferral() must not go negative.');
	}
}
