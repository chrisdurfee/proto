<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Api\ResourceHelper;
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
		unset($GLOBALS['router'], $GLOBALS['includedApiFlag']);
		parent::tearDown();
	}

	/**
	 * @return Router
	 */
	private function deferredRouter(string $path): Router
	{
		HttpRequest::reset();
		$http = new \ReflectionClass(HttpRequest::class);
		$http->getProperty('httpMethod')->setValue(null, 'GET');
		$http->getProperty('currentPath')->setValue(null, $path);

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

	/**
	 * includeApi() is a plain require in the current defer frame.
	 *
	 * @return void
	 */
	public function testIncludeApiRegistersLiteralChildWithoutFlush(): void
	{
		$router = $this->deferredRouter('/post/feed');
		$GLOBALS['router'] = $router;
		$router->resourceStrict('post', \stdClass::class);

		$path = $this->createTempFile(
			'<?php router()->get(\'post/feed\', static fn() => (object)[\'ok\' => true]);',
			'php'
		);
		$router->includeApi($path);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/post/feed', $pending->uri());
		$router->endDeferral();
	}

	/**
	 * includeApi() must not start or end a deferral frame.
	 *
	 * @return void
	 */
	public function testIncludeApiDoesNotFlushOrDefer(): void
	{
		$source = $this->methodSource(Router::class, 'includeApi');
		$this->assertStringNotContainsString('flushDeferred', $source);
		$this->assertStringNotContainsString('deferActivation', $source);
		$this->assertStringNotContainsString('endDeferral', $source);

		$router = $this->deferredRouter('/post/feed');
		$GLOBALS['router'] = $router;
		$router->resourceStrict('post', \stdClass::class);
		$before = $router->pendingRoute()?->uri();
		$depth = $this->deferDepth($router);

		$GLOBALS['includedApiFlag'] = false;
		$path = $this->createTempFile('<?php $GLOBALS[\'includedApiFlag\'] = true;', 'php');
		$router->includeApi($path);

		$this->assertTrue($GLOBALS['includedApiFlag']);
		$this->assertSame($before, $router->pendingRoute()?->uri());
		$this->assertSame($depth, $this->deferDepth($router));
		$router->endDeferral();
	}

	/**
	 * Nested includeResource() must not flush the parent pending match.
	 *
	 * @return void
	 */
	public function testNestedIncludeResourceDoesNotFlush(): void
	{
		$router = $this->deferredRouter('/post/feed');
		$GLOBALS['router'] = $router;
		$router->get('post/:id', static fn() => (object)['id' => true]);
		$this->assertStringEndsWith('/post/:id', $router->pendingRoute()?->uri() ?? '');
		$depth = $this->deferDepth($router);

		$path = $this->createTempFile(
			'<?php router()->get(\'post/feed\', static fn() => (object)[\'ok\' => true]);',
			'php'
		);
		ResourceHelper::includeResource($path);

		$pending = $router->pendingRoute();
		$this->assertNotNull($pending);
		$this->assertStringEndsWith('/post/feed', $pending->uri());
		$this->assertSame($depth, $this->deferDepth($router));
		$this->assertTrue($router->isDeferred());
		$router->endDeferral();
	}

	/**
	 * @param class-string $class
	 * @param string $method
	 * @return string
	 */
	private function methodSource(string $class, string $method): string
	{
		$ref = new \ReflectionMethod($class, $method);
		$file = $ref->getFileName();
		$this->assertNotFalse($file);
		$lines = file($file);
		$this->assertIsArray($lines);
		$start = $ref->getStartLine() - 1;
		$length = $ref->getEndLine() - $start;
		return implode('', array_slice($lines, $start, $length));
	}

	/**
	 * @param Router $router
	 * @return int
	 */
	private function deferDepth(Router $router): int
	{
		$ref = new \ReflectionProperty(Router::class, 'deferDepth');
		return (int)$ref->getValue($router);
	}
}
