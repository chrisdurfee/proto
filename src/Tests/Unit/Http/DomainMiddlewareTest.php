<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\Middleware\DomainMiddleware;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * DomainMiddlewareTest
 *
 * The middleware must judge the caller (Origin, else Referer), not the
 * API's own Host header, which is always the API host.
 *
 * @package Proto\Tests\Unit\Http
 */
final class DomainMiddlewareTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param array<string, string> $headers
	 * @return bool True when the request passed through.
	 */
	private function passes(array $headers): bool
	{
		$request = new DomainTestRequest($headers);
		$middleware = new DomainTestMiddleware();

		try
		{
			return $middleware->handle($request, fn() => true) === true;
		}
		catch (\RuntimeException)
		{
			return false;
		}
	}

	/**
	 * @return void
	 */
	public function testForeignOriginIsBlockedEvenThoughHostIsAllowed(): void
	{
		$this->assertFalse($this->passes([
			'origin' => 'https://evil.example',
			'host' => 'api.rally.test'
		]));
	}

	/**
	 * @return void
	 */
	public function testAllowedOriginPasses(): void
	{
		$this->assertTrue($this->passes([
			'origin' => 'https://app.rally.test',
			'host' => 'api.rally.test'
		]));
	}

	/**
	 * @return void
	 */
	public function testSameOriginPasses(): void
	{
		$this->assertTrue($this->passes([
			'origin' => 'https://backend.internal:8443',
			'host' => 'backend.internal:8443'
		]));
	}

	/**
	 * A cross-site GET (image, navigation) sends Referer but no Origin.
	 *
	 * @return void
	 */
	public function testForeignRefererWithoutOriginIsBlocked(): void
	{
		$this->assertFalse($this->passes([
			'referer' => 'https://evil.example/page',
			'host' => 'api.rally.test'
		]));
	}

	/**
	 * @return void
	 */
	public function testNullOriginIsBlocked(): void
	{
		$this->assertFalse($this->passes([
			'origin' => 'null',
			'host' => 'api.rally.test'
		]));
	}

	/**
	 * Non-browser clients send neither header.
	 *
	 * @return void
	 */
	public function testNoOriginOrRefererPasses(): void
	{
		$this->assertTrue($this->passes(['host' => 'api.rally.test']));
	}
}

/**
 * Request with fixed headers.
 */
final class DomainTestRequest extends Request
{
	/**
	 * @param array<string, string> $headers
	 */
	public function __construct(private array $headers)
	{
	}

	public function header(string $name): ?string
	{
		return $this->headers[strtolower($name)] ?? null;
	}
}

/**
 * Middleware with a fixed allow-list that throws instead of exiting.
 */
final class DomainTestMiddleware extends DomainMiddleware
{
	protected function allowedHosts(): array
	{
		return ['rally.test', 'app.rally.test', 'api.rally.test'];
	}

	protected function error(string $msg, int $responseCode): void
	{
		throw new \RuntimeException($msg);
	}
}
