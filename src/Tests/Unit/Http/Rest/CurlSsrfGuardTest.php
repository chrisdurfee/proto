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

	/**
	 * A redirect to a private-network target must be blocked, and the
	 * private target must never actually be requested. Real DNS/network
	 * I/O is avoided by using literal IPs (no lookup required) and by
	 * stubbing the low-level cURL response/redirect-URL hooks so the
	 * guarded redirect loop can be exercised deterministically.
	 *
	 * @return void
	 */
	public function testGuardedRedirectToPrivateTargetIsBlocked(): void
	{
		$curl = (new StubRedirectCurl(
			httpCodes: [302],
			redirectUrls: ['http://169.254.169.254/latest/meta-data/'],
			bodies: [null]
		))->denyPrivateNetworks();

		$response = $curl->request('http://8.8.8.8/start', 'GET');

		$this->assertSame(0, $response->code);
		$this->assertNull($response->data);
		$this->assertSame(
			['http://8.8.8.8/start'],
			$curl->requestedUrls,
			'The private redirect target must never actually be requested.'
		);
	}

	/**
	 * A redirect chain that stays entirely on public targets must still
	 * be followed end-to-end and return the final response.
	 *
	 * @return void
	 */
	public function testGuardedRedirectChainToPublicTargetSucceeds(): void
	{
		$curl = (new StubRedirectCurl(
			httpCodes: [302, 200],
			redirectUrls: ['http://8.8.4.4/final', null],
			bodies: [null, 'ok']
		))->denyPrivateNetworks();

		$response = $curl->request('http://8.8.8.8/start', 'GET');

		$this->assertSame(200, $response->code);
		$this->assertSame('ok', $response->data);
		$this->assertSame(
			['http://8.8.8.8/start', 'http://8.8.4.4/final'],
			$curl->requestedUrls
		);
	}

	/**
	 * A redirect chain longer than the guard's cap must stop following
	 * and return the last response reached instead of looping forever.
	 *
	 * @return void
	 */
	public function testGuardedRedirectChainIsCappedAtMaxRedirects(): void
	{
		// 7 redirects offered; the guard caps at 5, so the 6th hop's
		// response (still a 302) is the last one returned instead of
		// following the 7th.
		$httpCodes = array_fill(0, 7, 302);
		$httpCodes[] = 200;

		$redirectUrls = [];
		for ($i = 1; $i <= 7; $i++)
		{
			$redirectUrls[] = "http://8.8.4.{$i}/hop";
		}
		$redirectUrls[] = null;

		$bodies = array_fill(0, 8, null);

		$curl = (new StubRedirectCurl($httpCodes, $redirectUrls, $bodies))->denyPrivateNetworks();

		$response = $curl->request('http://8.8.8.8/start', 'GET');

		// Started + 5 followed redirects = 6 requests total, stopping
		// while the 6th response is still a redirect.
		$this->assertCount(6, $curl->requestedUrls);
		$this->assertSame(302, $response->code);
	}
}

/**
 * Test double that stubs cURL's low-level response/redirect hooks so
 * the guarded manual redirect loop in {@see Curl::requestWithRedirectGuard()}
 * can be exercised deterministically without any real network I/O or
 * DNS resolution.
 *
 * @package Proto\Tests\Unit\Http\Rest
 */
final class StubRedirectCurl extends Curl
{
	/**
	 * URLs passed to setUrl(), in call order.
	 *
	 * @var array<int, string>
	 */
	public array $requestedUrls = [];

	/**
	 * @var int
	 */
	private int $step = 0;

	/**
	 * @param array<int, int> $httpCodes HTTP status code returned for each successive request.
	 * @param array<int, string|null> $redirectUrls The `Location` cURL would report for each successive request (null = no redirect).
	 * @param array<int, mixed> $bodies The response body for each successive request.
	 */
	public function __construct(
		private readonly array $httpCodes,
		private readonly array $redirectUrls,
		private readonly array $bodies
	)
	{
		parent::__construct();
	}

	/**
	 * @param string $url
	 * @return self
	 */
	protected function setUrl(string $url): self
	{
		$this->requestedUrls[] = $url;
		return $this;
	}

	/**
	 * @return mixed
	 */
	protected function executeRequest(): mixed
	{
		return $this->bodies[$this->step] ?? null;
	}

	/**
	 * @return int
	 */
	protected function getHttpCode(): int
	{
		return $this->httpCodes[$this->step] ?? 0;
	}

	/**
	 * @return string|null
	 */
	protected function getRedirectUrl(): ?string
	{
		$location = $this->redirectUrls[$this->step] ?? null;
		$this->step++;
		return $location;
	}

	/**
	 * @return void
	 */
	protected function close(): void
	{
		// No real cURL handle state to clean up between stubbed steps.
	}
}
