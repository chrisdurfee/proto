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
	 * An optional parameter followed by more segments used to compile
	 * without an end anchor and catch any longer URL.
	 *
	 * @return void
	 */
	public function testOptionalParamFollowedBySegmentIsAnchored(): void
	{
		$route = $this->route('/api/post/:id?/edit');

		$this->assertEquals((object)['id' => '7'], $this->dispatch($route, '/api/post/7/edit'));
		$this->assertTrue($route->match('/api/post/edit', 'GET'));
		$this->assertFalse($route->match('/api/post/7/edit/delete', 'GET'));
		$this->assertFalse($route->match('/api/post/7/other-edit', 'GET'));
		$this->assertFalse($route->match('/api/post/7', 'GET'));
	}

	/**
	 * A trailing slash after an optional parameter must not turn the
	 * route into a prefix match.
	 *
	 * @return void
	 */
	public function testTrailingSlashAfterOptionalParamIsAnchored(): void
	{
		$route = $this->route('/api/post/:id?/');

		$this->assertEquals((object)['id' => '7'], $this->dispatch($route, '/api/post/7'));
		$this->assertTrue($route->match('/api/post', 'GET'));
		$this->assertFalse($route->match('/api/post/7/comments', 'GET'));
	}

	/**
	 * A dotted parameter matches one segment ending in the suffix.
	 *
	 * @return void
	 */
	public function testDottedParamIsAnchoredAndKeepsSuffix(): void
	{
		$route = $this->route('/files/:name.json');

		$this->assertEquals((object)['name' => 'report.json'], $this->dispatch($route, '/files/report.json'));
		$this->assertFalse($route->match('/files/report.json/../../secret', 'GET'));
		$this->assertFalse($route->match('/files/report.txt', 'GET'));
		$this->assertFalse($route->match('/files/a/b.json', 'GET'));
	}

	/**
	 * Two optional parameters in a row.
	 *
	 * @return void
	 */
	public function testConsecutiveOptionalParams(): void
	{
		$route = $this->route('/a/:id?/:sub?');

		$this->assertEquals((object)['id' => '1', 'sub' => '2'], $this->dispatch($route, '/a/1/2'));
		$this->assertEquals((object)['id' => '1'], $this->dispatch($route, '/a/1'));
		$this->assertTrue($route->match('/a', 'GET'));
		$this->assertFalse($route->match('/a/1/2/3', 'GET'));
	}

	/**
	 * A wildcard suffix inside a segment still matches any tail.
	 *
	 * @return void
	 */
	public function testSegmentWildcardSuffixMatchesTail(): void
	{
		$route = $this->route('/api/developer/migration*');

		$this->assertTrue($route->match('/api/developer/migration', 'GET'));
		$this->assertTrue($route->match('/api/developer/migrations/up', 'GET'));
		$this->assertFalse($route->match('/api/developer/table', 'GET'));
	}

	/**
	 * Literal regex characters in a route are matched literally.
	 *
	 * @return void
	 */
	public function testLiteralDotsAreEscaped(): void
	{
		$route = $this->route('/api/v1.0/status');

		$this->assertTrue($route->match('/api/v1.0/status', 'GET'));
		$this->assertFalse($route->match('/api/v1x0/status', 'GET'));
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
