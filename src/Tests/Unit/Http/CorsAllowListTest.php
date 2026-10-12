<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\Router\Headers;
use Proto\Tests\Test;
use ReflectionMethod;

/**
 * CorsAllowListTest
 *
 * An empty cors.allowedOrigins must not reflect every Origin outside
 * local/dev/testing — that is how credentials leak across sites when
 * prod forgets to configure the allow-list.
 *
 * @package Proto\Tests\Unit\Http
 */
final class CorsAllowListTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var mixed
	 */
	private mixed $originalEnv = null;

	/**
	 * @var mixed
	 */
	private mixed $originalCors = null;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->originalEnv = env('env');
		$this->originalCors = env('cors');
		Headers::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		setEnv('env', $this->originalEnv);
		setEnv('cors', $this->originalCors);
		Headers::reset();
		parent::tearDown();
	}

	/**
	 * @param string $origin
	 * @return bool
	 */
	private function isAllowed(string $origin): bool
	{
		$method = new ReflectionMethod(Headers::class, 'isAllowedOrigin');
		return $method->invoke(null, $origin);
	}

	/**
	 * @return void
	 */
	public function testEmptyAllowListIsOpenInDev(): void
	{
		setEnv('env', 'dev');
		setEnv('cors', (object)['allowedOrigins' => []]);
		Headers::reset();

		$this->assertTrue($this->isAllowed('https://evil.example'));
	}

	/**
	 * @return void
	 */
	public function testEmptyAllowListIsClosedInProd(): void
	{
		setEnv('env', 'prod');
		setEnv('cors', (object)['allowedOrigins' => []]);
		Headers::reset();

		$this->assertFalse($this->isAllowed('https://evil.example'));
	}

	/**
	 * @return void
	 */
	public function testEmptyAllowListIsClosedInStaging(): void
	{
		setEnv('env', 'staging');
		setEnv('cors', (object)['allowedOrigins' => []]);
		Headers::reset();

		$this->assertFalse($this->isAllowed('https://evil.example'));
	}

	/**
	 * @return void
	 */
	public function testConfiguredOriginIsAllowedInProd(): void
	{
		setEnv('env', 'prod');
		setEnv('cors', (object)['allowedOrigins' => ['https://app.example.com']]);
		Headers::reset();

		$this->assertTrue($this->isAllowed('https://app.example.com'));
		$this->assertFalse($this->isAllowed('https://evil.example'));
	}
}
