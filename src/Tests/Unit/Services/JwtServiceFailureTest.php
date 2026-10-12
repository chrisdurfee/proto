<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Services;

use Proto\Http\Rest\Response;
use Proto\Integrations\IntegrationException;
use Proto\Integrations\JwtService;
use Proto\Tests\Test;

/**
 * JwtServiceFailureTest
 *
 * A failed upstream token request used to die(), silently killing the
 * whole PHP process (including long-running workers). It must throw an
 * IntegrationException the caller can handle instead.
 *
 * @package Proto\Tests\Unit\Services
 */
final class JwtServiceFailureTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Builds the service without running its network-bound constructor.
	 *
	 * @return FakeJwtService
	 */
	private function service(): FakeJwtService
	{
		return (new \ReflectionClass(FakeJwtService::class))->newInstanceWithoutConstructor();
	}

	/**
	 * @return void
	 */
	public function testUpstreamFailureThrowsInsteadOfExiting(): void
	{
		$this->expectException(IntegrationException::class);
		$this->expectExceptionCode(401);

		$this->service()->applyResult(new Response(401, '{"error":"denied"}'));
	}

	/**
	 * @return void
	 */
	public function testSuccessWithoutTokenThrows(): void
	{
		$this->expectException(IntegrationException::class);

		$this->service()->applyResult(new Response(200, null));
	}

	/**
	 * @return void
	 */
	public function testSuccessStoresToken(): void
	{
		$service = $this->service();
		$service->applyResult(new Response(201, '{"token":"abc.def.ghi"}'));

		$this->assertSame('abc.def.ghi', $service->jwtValue());
	}
}

/**
 * Concrete JwtService exposing setupJwt() for tests.
 */
final class FakeJwtService extends JwtService
{
	/**
	 * @param object $result
	 * @return void
	 */
	public function applyResult(object $result): void
	{
		$this->setupJwt($result);
	}

	/**
	 * @return string
	 */
	public function jwtValue(): string
	{
		return $this->jwt;
	}
}
