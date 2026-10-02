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
}
