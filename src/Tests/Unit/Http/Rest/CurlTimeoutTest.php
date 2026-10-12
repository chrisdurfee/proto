<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Rest;

use Proto\Http\Rest\Curl;
use Proto\Tests\Test;
use ReflectionClass;
use ReflectionMethod;

/**
 * CurlTimeoutTest
 *
 * Outbound cURL transfers must always carry connect and total timeouts so a
 * hung peer cannot pin PHP-FPM workers indefinitely.
 *
 * @package Proto\Tests\Unit\Http\Rest
 */
final class CurlTimeoutTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param Curl $curl
	 * @param string $property
	 * @return mixed
	 */
	private function prop(Curl $curl, string $property): mixed
	{
		$ref = new ReflectionClass($curl);
		$prop = $ref->getProperty($property);
		return $prop->getValue($curl);
	}

	/**
	 * @return void
	 */
	public function testDefaultsAreThirtyAndTenSeconds(): void
	{
		$curl = new Curl();
		$this->assertSame(30, $this->prop($curl, 'timeoutSeconds'));
		$this->assertSame(10, $this->prop($curl, 'connectTimeoutSeconds'));
	}

	/**
	 * @return void
	 */
	public function testTimeoutSettersOverrideDefaults(): void
	{
		$curl = new Curl();
		$curl->connectTimeout(3)->timeout(12);

		$this->assertSame(12, $this->prop($curl, 'timeoutSeconds'));
		$this->assertSame(3, $this->prop($curl, 'connectTimeoutSeconds'));
	}

	/**
	 * @return void
	 */
	public function testTimeoutSettersFloorAtOneSecond(): void
	{
		$curl = new Curl();
		$curl->connectTimeout(0)->timeout(-5);

		$this->assertSame(1, $this->prop($curl, 'timeoutSeconds'));
		$this->assertSame(1, $this->prop($curl, 'connectTimeoutSeconds'));
	}

	/**
	 * configureOptions() applies the configured timeouts to the handle.
	 *
	 * @return void
	 */
	public function testConfigureOptionsAppliesTimeouts(): void
	{
		$curl = new Curl();
		$curl->connectTimeout(4)->timeout(9);

		$method = new ReflectionMethod(Curl::class, 'configureOptions');
		$method->invoke($curl);

		$handle = $this->prop($curl, 'curl');
		$this->assertInstanceOf(\CurlHandle::class, $handle);
		$this->assertSame(4, $this->prop($curl, 'connectTimeoutSeconds'));
		$this->assertSame(9, $this->prop($curl, 'timeoutSeconds'));
	}
}
