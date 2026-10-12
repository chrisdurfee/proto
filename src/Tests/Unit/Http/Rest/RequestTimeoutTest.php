<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Rest;

use Proto\Http\Rest\Curl;
use Proto\Http\Rest\Request;
use Proto\Tests\Test;
use ReflectionProperty;

/**
 * RequestTimeoutTest
 *
 * Request must expose fluent connectTimeout()/timeout() setters that
 * reach the Curl instance it builds for each call.
 *
 * @package Proto\Tests\Unit\Http\Rest
 */
final class RequestTimeoutTest extends Test
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
		return (new ReflectionProperty(Curl::class, $property))->getValue($curl);
	}

	/**
	 * @return void
	 */
	public function testSettersPassThroughToCurl(): void
	{
		$request = new RequestTimeoutHarness('https://example.com');
		$result = $request->connectTimeout(3)->timeout(12);

		$this->assertSame($request, $result);

		$curl = $request->exposeBuildCurl();
		$this->assertSame(3, $this->prop($curl, 'connectTimeoutSeconds'));
		$this->assertSame(12, $this->prop($curl, 'timeoutSeconds'));
	}

	/**
	 * @return void
	 */
	public function testCurlDefaultsApplyWhenUnset(): void
	{
		$curl = (new RequestTimeoutHarness())->exposeBuildCurl();

		$this->assertSame(10, $this->prop($curl, 'connectTimeoutSeconds'));
		$this->assertSame(30, $this->prop($curl, 'timeoutSeconds'));
	}

	/**
	 * @return void
	 */
	public function testSettersFloorAtOneSecond(): void
	{
		$curl = (new RequestTimeoutHarness())->connectTimeout(0)->timeout(-4)->exposeBuildCurl();

		$this->assertSame(1, $this->prop($curl, 'connectTimeoutSeconds'));
		$this->assertSame(1, $this->prop($curl, 'timeoutSeconds'));
	}
}

/**
 * Exposes Request::buildCurl() without making a network call.
 */
final class RequestTimeoutHarness extends Request
{
	/**
	 * @return Curl
	 */
	public function exposeBuildCurl(): Curl
	{
		return $this->buildCurl();
	}
}
