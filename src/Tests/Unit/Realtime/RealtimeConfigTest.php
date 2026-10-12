<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Realtime;

use PHPUnit\Framework\TestCase;
use Proto\Realtime\Server\RealtimeConfig;

/**
 * RealtimeConfigTest
 *
 * @package Proto\Tests\Unit\Realtime
 */
final class RealtimeConfigTest extends TestCase
{
	private const SECRET = 'config-secret-0123456789-abcdefghijklmnop';

	/**
	 * The upstream limiter defaults protect PHP-FPM without any config.
	 *
	 * @return void
	 */
	public function testUpstreamLimitDefaults(): void
	{
		$config = RealtimeConfig::fromEnv((object)['secret' => self::SECRET, 'upstream' => 'http://web'], null);

		$this->assertSame(32, $config->upstreamConcurrency);
		$this->assertSame(2048, $config->upstreamMaxQueued);
	}

	/**
	 * Env values are read, and nonsense is clamped instead of disabling
	 * the limit.
	 *
	 * @return void
	 */
	public function testUpstreamLimitsFromEnvAreClamped(): void
	{
		$config = RealtimeConfig::fromEnv((object)[
			'secret' => self::SECRET,
			'upstream' => 'http://web',
			'upstreamConcurrency' => 0,
			'upstreamMaxQueued' => -5
		], null);
		$this->assertSame(1, $config->upstreamConcurrency);
		$this->assertSame(0, $config->upstreamMaxQueued);

		$config = RealtimeConfig::fromEnv((object)[
			'secret' => self::SECRET,
			'upstream' => 'http://web',
			'upstreamConcurrency' => 8,
			'upstreamMaxQueued' => 100
		], null);
		$this->assertSame(8, $config->upstreamConcurrency);
		$this->assertSame(100, $config->upstreamMaxQueued);
	}

	/**
	 * @return void
	 */
	public function testConstructorRejectsZeroConcurrency(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new RealtimeConfig(secret: self::SECRET, upstream: 'http://web', redisUri: 'redis://r:6379', upstreamConcurrency: 0);
	}

	/**
	 * Connection caps default on and are read from env (clamped >= 1).
	 *
	 * @return void
	 */
	public function testConnectionCapsDefaultAndClamp(): void
	{
		$config = RealtimeConfig::fromEnv((object)['secret' => self::SECRET, 'upstream' => 'http://web'], null);
		$this->assertSame(20000, $config->maxConnections);
		$this->assertSame(100, $config->maxConnectionsPerIp);

		$config = RealtimeConfig::fromEnv((object)[
			'secret' => self::SECRET,
			'upstream' => 'http://web',
			'maxConnections' => 0,
			'maxConnectionsPerIp' => 5
		], null);
		$this->assertSame(1, $config->maxConnections);
		$this->assertSame(5, $config->maxConnectionsPerIp);
	}

	/**
	 * The handler refuses new streams past the per-IP and global caps
	 * and frees slots on release.
	 *
	 * @return void
	 */
	public function testStreamSlotsEnforcePerIpAndGlobalCaps(): void
	{
		$config = new RealtimeConfig(self::SECRET, 'http://web', 'redis://localhost:6379', maxConnections: 3, maxConnectionsPerIp: 2);
		$ref = new \ReflectionClass(\Proto\Realtime\Server\SseRequestHandler::class);
		$handler = $ref->newInstanceWithoutConstructor();
		$ref->getProperty('config')->setValue($handler, $config);

		$acquire = $ref->getMethod('acquireSlot');
		$release = $ref->getMethod('releaseSlot');

		$this->assertTrue($acquire->invoke($handler, '1.1.1.1'));
		$this->assertTrue($acquire->invoke($handler, '1.1.1.1'));
		$this->assertFalse($acquire->invoke($handler, '1.1.1.1'), 'Per-IP cap.');
		$this->assertTrue($acquire->invoke($handler, '2.2.2.2'));
		$this->assertFalse($acquire->invoke($handler, '3.3.3.3'), 'Global cap.');

		$release->invoke($handler, '1.1.1.1');
		$this->assertTrue($acquire->invoke($handler, '3.3.3.3'));
	}
}
