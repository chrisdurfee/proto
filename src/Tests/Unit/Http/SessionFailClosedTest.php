<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Config;
use Proto\Http\Cookie;
use Proto\Http\Session;
use Proto\Http\Session\SessionUnavailableException;
use Proto\Tests\Test;
use ReflectionProperty;

/**
 * SessionFailClosedTest
 *
 * When the configured session store is down, Session::init() must fail
 * closed (503) instead of silently moving users onto per-server file
 * sessions, unless `sessionFallback: "file"` opts back in. Native
 * session cookies must carry the framework's security attributes.
 *
 * @package Proto\Tests\Unit\Http
 */
final class SessionFailClosedTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var mixed
	 */
	private mixed $savedFallback = null;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->savedFallback = Config::access('sessionFallback');
		$this->typeProperty()->setValue(null, UnreachableSessionStore::class);
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		Config::getInstance()->set('sessionFallback', $this->savedFallback);
		$this->typeProperty()->setValue(null, null);
		(new ReflectionProperty(Session::class, 'instance'))->setValue(null, null);
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testUnreachableStoreFailsClosedByDefault(): void
	{
		Config::getInstance()->set('sessionFallback', null);

		$this->expectException(SessionUnavailableException::class);
		Session::init();
	}

	/**
	 * @return void
	 */
	public function testSessionUnavailableIsAServiceOutage(): void
	{
		$this->assertInstanceOf(
			\Proto\Error\ServiceUnavailableException::class,
			new SessionUnavailableException('down')
		);
	}

	/**
	 * @return void
	 */
	public function testSessionCookieParamsAreHttpOnly(): void
	{
		$params = Cookie::sessionCookieParams();

		$this->assertSame(0, $params['lifetime']);
		$this->assertTrue($params['httponly']);
		$this->assertArrayHasKey('secure', $params);
		$this->assertContains($params['samesite'], ['Strict', 'Lax']);
		$this->assertArrayNotHasKey('expires', $params);
	}

	/**
	 * @return ReflectionProperty
	 */
	private function typeProperty(): ReflectionProperty
	{
		return new ReflectionProperty(Session::class, 'type');
	}
}

/**
 * Session store whose init always fails, like Redis being down.
 *
 * @package Proto\Tests\Unit\Http
 */
final class UnreachableSessionStore
{
	/**
	 * @return never
	 */
	public static function init(): never
	{
		throw new \RuntimeException('sessionConnection is set but Redis is unreachable.');
	}
}
