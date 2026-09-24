<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Proto\Http\Cookie;

/**
 * CookieDomainTest
 *
 * Domain-wide cookies stay off unless explicitly enabled, and even
 * then only for a production host under the configured parent.
 *
 * @package Proto\Tests\Unit\Http
 */
final class CookieDomainTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testDisabledStaysHostOnly(): void
	{
		$this->assertNull(Cookie::resolveSharedDomain('app.rally-app.co', false, 'rally-app.co'));
	}

	/**
	 * @return void
	 */
	public function testProductionHostUsesParentDomain(): void
	{
		$this->assertSame(
			'.rally-app.co',
			Cookie::resolveSharedDomain('app.rally-app.co', true, 'rally-app.co')
		);
		$this->assertSame(
			'.rally-app.co',
			Cookie::resolveSharedDomain('rally-app.co', true, 'rally-app.co')
		);
		$this->assertSame(
			'.rally-app.co',
			Cookie::resolveSharedDomain('CRM.rally-app.co', true, 'rally-app.co')
		);
	}

	/**
	 * @return void
	 */
	public function testLocalAndForeignHostsStayHostOnly(): void
	{
		$this->assertNull(Cookie::resolveSharedDomain('localhost', true, 'rally-app.co'));
		$this->assertNull(Cookie::resolveSharedDomain('localhost:3000', true, 'rally-app.co'));
		$this->assertNull(Cookie::resolveSharedDomain('127.0.0.1', true, 'rally-app.co'));
		$this->assertNull(Cookie::resolveSharedDomain('evil.com', true, 'rally-app.co'));
		$this->assertNull(Cookie::resolveSharedDomain('notrally-app.co', true, 'rally-app.co'));
		$this->assertNull(Cookie::resolveSharedDomain('app.rally-app.co', true, 'localhost'));
		$this->assertNull(Cookie::resolveSharedDomain('app.rally-app.co', true, null));
	}
}
