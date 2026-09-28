<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Auth\Gates;

use PHPUnit\Framework\TestCase;
use Proto\Auth\Gates\CrossSiteRequestForgeryGate;
use Proto\Http\Cookie;
use ReflectionClass;

/**
 * CrossSiteRequestForgeryGateTest
 *
 * Covers the readable XSRF cookie delivery helpers without needing a
 * live session or header bag.
 *
 * @package Proto\Tests\Unit\Auth\Gates
 */
final class CrossSiteRequestForgeryGateTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testCookieNameAndHeaderConstants(): void
	{
		$this->assertSame('XSRF-TOKEN', CrossSiteRequestForgeryGate::COOKIE_NAME);
		$this->assertSame('x-xsrf-token', CrossSiteRequestForgeryGate::HEADER_NAME);
		$this->assertSame('csrf-token', CrossSiteRequestForgeryGate::CSRF_TOKEN);
	}

	/**
	 * @return void
	 */
	public function testCookieConstructorAcceptsReadableFlag(): void
	{
		$cookie = new Cookie('XSRF-TOKEN', 'abc', 0, false);
		$reflection = new ReflectionClass($cookie);
		$property = $reflection->getProperty('httpOnly');
		$property->setAccessible(true);

		$this->assertFalse($property->getValue($cookie));
		$this->assertSame('XSRF-TOKEN', $cookie->getName());
		$this->assertSame('abc', $cookie->getValue());
	}
}
