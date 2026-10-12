<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Router\Request;
use Proto\Http\Router\Route;
use Proto\Http\Router\UriQuery;
use Proto\Tests\Test;

/**
 * RouteMatchTest
 *
 * Route/Uri pattern compilation, method matching and parameter
 * extraction, exercised directly so nothing reaches Router::exit.
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class RouteMatchTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param string $uri
	 * @param string $method
	 * @return Route
	 */
	private function route(string $uri, string $method = 'GET'): Route
	{
		return new Route($method, $uri, static fn(Request $req) => $req->params());
	}

	/**
	 * Matches and returns the params the callback would receive.
	 *
	 * @param Route $route
	 * @param string $path
	 * @return object|null|false False when the route does not match.
	 */
	private function dispatch(Route $route, string $path, string $method = 'GET'): object|null|false
	{
		if (!$route->match($path, $method))
		{
			return false;
		}
		return $route->activate(new Request());
	}

	/**
	 * @return void
	 */
	public function testLiteralRouteMatchesExactlyAndHasNoParams(): void
	{
		$route = $this->route('/api/health');

		$this->assertNull($this->dispatch($route, '/api/health'));
		$this->assertFalse($route->match('/api/health/x', 'GET'));
		$this->assertFalse($route->match('/api/healthz', 'GET'));
		$this->assertFalse($route->match('/prefix/api/health', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testRequiredParamIsExtracted(): void
	{
		$route = $this->route('/api/user/:id');

		$this->assertEquals((object)['id' => '5'], $this->dispatch($route, '/api/user/5'));
		$this->assertFalse($route->match('/api/user', 'GET'));
		$this->assertFalse($route->match('/api/user/5/extra', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testMultipleParamsAreExtractedByName(): void
	{
		$route = $this->route('/api/group/:groupId/post/:postId');

		$this->assertEquals(
			(object)['groupId' => 'g1', 'postId' => '42'],
			$this->dispatch($route, '/api/group/g1/post/42')
		);
	}

	/**
	 * @return void
	 */
	public function testParamDoesNotSpanSegments(): void
	{
		$this->assertFalse($this->route('/api/user/:id')->match('/api/user/5/6', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testOptionalTrailingParam(): void
	{
		$route = $this->route('/api/post/:id?');

		$this->assertEquals((object)['id' => '7'], $this->dispatch($route, '/api/post/7'));
		$this->assertEquals((object)[], $this->dispatch($route, '/api/post'));
		$this->assertFalse($route->match('/api/postx', 'GET'));
		$this->assertFalse($route->match('/api/post/7/8', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testOptionalParamAfterRequiredParam(): void
	{
		$route = $this->route('/api/user/:userId/post/:id?');

		$this->assertEquals((object)['userId' => '3', 'id' => '9'], $this->dispatch($route, '/api/user/3/post/9'));
		$this->assertEquals((object)['userId' => '3'], $this->dispatch($route, '/api/user/3/post'));
		$this->assertFalse($route->match('/api/user/3', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testWildcardMatchesAnyTail(): void
	{
		$route = $this->route('/api/files/*');

		$this->assertTrue($route->match('/api/files/a/b/c.txt', 'GET'));
		$this->assertFalse($route->match('/api/other/a', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testEmptyUriMatchesEverything(): void
	{
		$this->assertSame('/.*/', UriQuery::create(''));
	}

	/**
	 * @return void
	 */
	public function testParamValuesAreNotDecoded(): void
	{
		$this->assertEquals((object)['id' => 'a%20b'], $this->dispatch($this->route('/u/:id'), '/u/a%20b'));
	}

	/**
	 * @return void
	 */
	public function testRegexMetacharactersInPathAreNotInjected(): void
	{
		$route = $this->route('/api/user/:id');
		$this->assertFalse($route->match('/api/user/5|6', 'GET'));
		$this->assertFalse($route->match('/api/user/a?b', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testMethodMustMatchCaseInsensitively(): void
	{
		$route = $this->route('/api/user/:id', 'post');

		$this->assertSame('POST', $route->getMethod());
		$this->assertTrue($route->match('/api/user/1', 'POST'));
		$this->assertTrue($route->match('/api/user/1', 'post'));
		$this->assertFalse($route->match('/api/user/1', 'GET'));
	}

	/**
	 * @return void
	 */
	public function testAllMatchesAnyMethod(): void
	{
		$route = $this->route('/api/user/:id', 'ALL');

		foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
		{
			$this->assertTrue($route->match('/api/user/1', $method), $method);
		}
	}

	/**
	 * @return void
	 */
	public function testMethodMismatchDoesNotCaptureParams(): void
	{
		$route = $this->route('/api/user/:id', 'DELETE');

		$this->assertFalse($route->match('/api/user/1', 'GET'));
		$this->assertNull($route->activate(new Request()));
	}

	/**
	 * @return void
	 */
	public function testActivateIgnoresIncomingRequestParams(): void
	{
		$route = $this->route('/api/user/:id');
		$route->match('/api/user/5', 'GET');

		$result = $route->activate(new Request((object)['id' => 'spoofed']));
		$this->assertSame('5', $result->id);
	}

	/**
	 * @return void
	 */
	public function testInitializeWithoutMiddlewareActivates(): void
	{
		$route = $this->route('/x/:id');
		$route->match('/x/1', 'GET');

		$this->assertEquals((object)['id' => '1'], $route->initialize([], new Request()));
	}

	/**
	 * Unknown middleware class names pass through rather than fatal.
	 *
	 * @return void
	 */
	public function testUnknownMiddlewareIsPassThrough(): void
	{
		$route = $this->route('/x/:id');
		$route->middleware(['No\\Such\\Middleware']);
		$route->match('/x/2', 'GET');

		$this->assertEquals((object)['id' => '2'], $route->initialize(['Also\\Missing'], new Request()));
	}
}
