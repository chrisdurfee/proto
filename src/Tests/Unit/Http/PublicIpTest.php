<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\PublicIp;
use Proto\Tests\Test;

/**
 * PublicIpTest
 *
 * Verifies the trusted-proxy `X-Forwarded-For` / `X-Real-IP` handling
 * in {@see PublicIp}. In particular, that a client-supplied spoofed
 * prefix on `X-Forwarded-For` cannot be used to impersonate another
 * IP address (the classic single-trusted-hop bypass of IP-based rate
 * limiting), while a legitimately configured trusted-proxy chain
 * still resolves the real client IP correctly.
 *
 * @package Proto\Tests\Unit\Http
 */
final class PublicIpTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var array<string, mixed>
	 */
	private array $serverBackup = [];

	/**
	 * @var mixed
	 */
	private mixed $trustedProxiesBackup = null;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->serverBackup = $_SERVER;
		$this->trustedProxiesBackup = env('trustedProxies');

		foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key)
		{
			unset($_SERVER[$key]);
		}

		PublicIp::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		$_SERVER = $this->serverBackup;
		setEnv('trustedProxies', $this->trustedProxiesBackup);
		PublicIp::reset();

		parent::tearDown();
	}

	/**
	 * With no trusted proxies configured, proxy headers are never
	 * consulted — REMOTE_ADDR is used directly. This is the pre-existing
	 * safe default and must be unaffected by the XFF algorithm change.
	 *
	 * @return void
	 */
	public function testUntrustedRemoteAddrIgnoresForwardedForHeader(): void
	{
		setEnv('trustedProxies', []);

		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';

		$this->assertSame('203.0.113.9', PublicIp::get());
	}

	/**
	 * The core regression test: with exactly one trusted proxy hop
	 * (the normal reverse-proxy topology), a client cannot spoof its
	 * IP by prepending an arbitrary address to X-Forwarded-For. The
	 * real client IP is the rightmost entry that is not itself the
	 * trusted proxy.
	 *
	 * @return void
	 */
	public function testSpoofedForwardedForPrefixIsIgnored(): void
	{
		setEnv('trustedProxies', ['10.0.0.1']);

		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		// Attacker-controlled prefix followed by the real client IP the
		// trusted proxy appended.
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 198.51.100.7';

		$this->assertSame(
			'198.51.100.7',
			PublicIp::get(),
			'The rightmost, non-trusted-proxy entry must win, not the attacker-controlled leftmost entry.'
		);
	}

	/**
	 * Rotating the spoofed prefix on every request must not change the
	 * resolved identity — proving the bypass is fully closed, not just
	 * incidentally avoided for one value.
	 *
	 * @return void
	 */
	public function testRotatingSpoofedPrefixDoesNotChangeResolvedIp(): void
	{
		setEnv('trustedProxies', ['10.0.0.1']);
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';

		foreach (['9.9.9.9', '8.8.8.8', '1.1.1.1'] as $spoofed)
		{
			PublicIp::reset();
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $spoofed . ', 198.51.100.7';
			$this->assertSame('198.51.100.7', PublicIp::get());
		}
	}

	/**
	 * A multi-hop trusted proxy chain (e.g. CDN -> internal load
	 * balancer) skips every trusted hop from the right and returns the
	 * first untrusted entry, still defeating a spoofed leftmost prefix.
	 *
	 * @return void
	 */
	public function testMultipleTrustedProxyHopsAreSkipped(): void
	{
		setEnv('trustedProxies', ['10.0.0.1', '10.0.0.2']);
		$_SERVER['REMOTE_ADDR'] = '10.0.0.2';

		// attacker, real client, hop 1 (10.0.0.1), hop 2 (10.0.0.2 appends itself before reaching us)
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6, 198.51.100.7, 10.0.0.1';

		$this->assertSame('198.51.100.7', PublicIp::get());
	}

	/**
	 * If every entry in the header matches a trusted proxy (should not
	 * normally happen), the implementation fails safe by falling back
	 * to the leftmost entry rather than returning nothing / crashing.
	 *
	 * @return void
	 */
	public function testAllTrustedEntriesFallsBackToLeftmost(): void
	{
		setEnv('trustedProxies', ['10.0.0.1', '10.0.0.2']);
		$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2';

		$this->assertSame('10.0.0.1', PublicIp::get());
	}

	/**
	 * The existing trusted-proxy CIDR configuration path continues to
	 * work for the (still supported) single-value X-Real-IP header.
	 *
	 * @return void
	 */
	public function testTrustedProxyCidrStillResolvesRealIpHeader(): void
	{
		setEnv('trustedProxies', ['10.0.0.0/8']);

		$_SERVER['REMOTE_ADDR'] = '10.1.2.3';
		$_SERVER['HTTP_X_REAL_IP'] = '198.51.100.42';

		$this->assertSame('198.51.100.42', PublicIp::get());
	}

	/**
	 * X-Real-IP / X-Client-IP are only trusted at all when REMOTE_ADDR
	 * itself is a trusted proxy — an untrusted REMOTE_ADDR means the
	 * header is ignored entirely and REMOTE_ADDR itself is used.
	 *
	 * @return void
	 */
	public function testUntrustedRemoteAddrIgnoresRealIpHeader(): void
	{
		setEnv('trustedProxies', ['10.0.0.1']);

		$_SERVER['REMOTE_ADDR'] = '203.0.113.55';
		$_SERVER['HTTP_X_REAL_IP'] = '198.51.100.42';

		$this->assertSame('203.0.113.55', PublicIp::get());
	}

	/**
	 * CF-Connecting-IP takes priority over X-Forwarded-For when both
	 * are present and the proxy is trusted (pre-existing header
	 * priority, unaffected by the algorithm change).
	 *
	 * @return void
	 */
	public function testCloudflareHeaderTakesPriorityOverForwardedFor(): void
	{
		setEnv('trustedProxies', ['10.0.0.1']);

		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.99';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 198.51.100.7';

		$this->assertSame('198.51.100.99', PublicIp::get());
	}
}
