<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http\Rest;

use Proto\Http\Rest\Curl;
use Proto\Tests\Test;

/**
 * CurlSsrfGuardTest
 *
 * Verifies the opt-in `Curl::denyPrivateNetworks()` SSRF guard: private
 * networks are rejected only when explicitly enabled (BC default is
 * unchanged), and public hosts are unaffected.
 *
 * @package Proto\Tests\Unit\Http\Rest
 */
final class CurlSsrfGuardTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testLoopbackAddressIsDetectedAsPrivate(): void
	{
		$this->assertTrue(Curl::isPrivateNetworkUrl('http://127.0.0.1/secrets'));
	}

	/**
	 * @return void
	 */
	public function testPrivateRfc1918AddressIsDetectedAsPrivate(): void
	{
		$this->assertTrue(Curl::isPrivateNetworkUrl('http://10.0.0.5/'));
		$this->assertTrue(Curl::isPrivateNetworkUrl('http://192.168.1.1/'));
	}

	/**
	 * @return void
	 */
	public function testLinkLocalAddressIsDetectedAsPrivate(): void
	{
		$this->assertTrue(Curl::isPrivateNetworkUrl('http://169.254.169.254/latest/meta-data/'));
	}

	/**
	 * A literal public IP is not flagged as private.
	 *
	 * @return void
	 */
	public function testPublicLiteralIpIsNotPrivate(): void
	{
		$this->assertFalse(Curl::isPrivateNetworkUrl('http://8.8.8.8/'));
	}

	/**
	 * A URL with no resolvable host fails closed (treated as unsafe).
	 *
	 * @return void
	 */
	public function testMissingHostFailsClosed(): void
	{
		$this->assertTrue(Curl::isPrivateNetworkUrl('not-a-url'));
	}

	/**
	 * With the guard disabled (default, BC), `isPrivateNetworkUrl()` is
	 * simply never consulted by request() — this is implicitly covered
	 * by every other Curl usage in the codebase not calling
	 * denyPrivateNetworks(); this test documents the flag's default.
	 *
	 * @return void
	 */
	public function testGuardIsOptInAndDisabledByDefault(): void
	{
		$curl = new Curl();
		$reflection = new \ReflectionProperty($curl, 'denyPrivateNetworksGuard');
		$reflection->setAccessible(true);

		$this->assertFalse($reflection->getValue($curl), 'denyPrivateNetworks() must be opt-in; default must stay disabled for BC.');
	}

	/**
	 * When the guard is enabled, request() blocks a private-network
	 * target before any cURL request is sent, returning a `code: 0`
	 * response instead of attempting the connection.
	 *
	 * @return void
	 */
	public function testRequestIsBlockedWhenGuardEnabledAndTargetIsPrivate(): void
	{
		$curl = (new Curl())->denyPrivateNetworks();

		$start = microtime(true);
		$response = $curl->request('http://127.0.0.1:1/', 'GET');
		$elapsed = microtime(true) - $start;

		$this->assertSame(0, $response->code);
		$this->assertNull($response->data);
		$this->assertLessThan(1.0, $elapsed, 'The guard must block before any network I/O, not merely time out fast.');
	}
}
