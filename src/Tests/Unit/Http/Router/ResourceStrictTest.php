<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Request as HttpRequest;
use Proto\Http\Router\Router;
use Proto\Tests\Test;

/**
 * ResourceStrictTest
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class ResourceStrictTest extends Test
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
	 * @return Router
	 */
	private function deferredRouter(string $path): Router
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = $path;
		HttpRequest::reset();

		$router = new Router('');
		$router->deferActivation();
		return $router;
	}

	/**
	 * @return void
	 */
	public function testLiteralChildOutranksIdWhenRegisteredAfter(): void
	{
		$router = $this->deferredRouter('/post/feed');
		$router->resourceStrict('post', \stdClass::class);
		$router->get('post/feed', static fn() => (object)['ok' => true]);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/post/feed', $pending->uri());
		$router->endDeferral();
	}

	/**
	 * @return void
	 */
	public function testLiteralChildOutranksIdWhenRegisteredBefore(): void
	{
		$router = $this->deferredRouter('/post/feed');
		$router->get('post/feed', static fn() => (object)['ok' => true]);
		$router->resourceStrict('post', \stdClass::class);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/post/feed', $pending->uri());
		$router->endDeferral();
	}

	/**
	 * @return void
	 */
	public function testNumericItemStillMatchesIdRoute(): void
	{
		$router = $this->deferredRouter('/post/5');
		$router->resourceStrict('post', \stdClass::class);
		$router->get('post/feed', static fn() => (object)['ok' => true]);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/post/:id', $pending->uri());
		$router->endDeferral();
	}
}
