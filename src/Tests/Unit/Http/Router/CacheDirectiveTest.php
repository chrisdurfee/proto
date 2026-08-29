<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Router;

use Proto\Http\Router\CacheDirective;
use Proto\Tests\Test;

/**
 * CacheDirectiveTest
 *
 * @package Proto\Tests\Unit\Http\Router
 */
final class CacheDirectiveTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testNoStoreForbidsStorageAndReuse(): void
	{
		$directive = CacheDirective::noStore();

		$this->assertStringContainsString('no-store', $directive->value());
		$this->assertFalse($directive->isStorable());
		$this->assertFalse($directive->isReusable());
	}

	/**
	 * The default must let the client keep a copy (so it can revalidate)
	 * while never allowing reuse without asking the server.
	 *
	 * @return void
	 */
	public function testRevalidateIsStorableButNotReusable(): void
	{
		$directive = CacheDirective::revalidate();

		$this->assertStringNotContainsString('no-store', $directive->value());
		$this->assertStringContainsString('no-cache', $directive->value());
		$this->assertStringContainsString('private', $directive->value());
		$this->assertTrue($directive->isStorable());
		$this->assertFalse($directive->isReusable());
	}

	/**
	 * @return void
	 */
	public function testPrivateForRendersMaxAge(): void
	{
		$directive = CacheDirective::privateFor(60);

		$this->assertSame('private, max-age=60', $directive->value());
		$this->assertTrue($directive->isStorable());
		$this->assertTrue($directive->isReusable());
	}

	/**
	 * @return void
	 */
	public function testPrivateForRendersStaleWhileRevalidate(): void
	{
		$this->assertSame(
			'private, max-age=30, stale-while-revalidate=120',
			CacheDirective::privateFor(30, 120)->value()
		);
	}

	/**
	 * A zero max-age cannot be reused without revalidation, so it must not
	 * widen Vary the way a real freshness window does.
	 *
	 * @return void
	 */
	public function testZeroMaxAgeIsNotReusable(): void
	{
		$this->assertFalse(CacheDirective::privateFor(0)->isReusable());
	}

	/**
	 * @return void
	 */
	public function testNegativeMaxAgeIsClampedToZero(): void
	{
		$this->assertSame('private, max-age=0', CacheDirective::privateFor(-5)->value());
	}

	/**
	 * @return void
	 */
	public function testPublicForRendersSharedMaxAge(): void
	{
		$directive = CacheDirective::publicFor(60, 300, 600);

		$this->assertSame(
			'public, max-age=60, s-maxage=300, stale-while-revalidate=600',
			$directive->value()
		);
		$this->assertTrue($directive->isReusable());
	}

	/**
	 * @return void
	 */
	public function testPublicForSharedOnlyIsStillReusable(): void
	{
		$this->assertTrue(CacheDirective::publicFor(0, 300)->isReusable());
	}

	/**
	 * @return void
	 */
	public function testCastsToHeaderValue(): void
	{
		$this->assertSame('private, max-age=15', (string)CacheDirective::privateFor(15));
	}
}
