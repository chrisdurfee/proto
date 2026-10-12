<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Drivers\RedisDriver;
use Proto\Tests\Test;

/**
 * RedisReconnectTest
 *
 * After a failure the driver stays offline for a short cooldown, then
 * reconnects, so long-running workers recover from a Redis blip.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class RedisReconnectTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testReconnectsOnlyAfterCooldown(): void
	{
		$driver = new FlakyRedisDriver();
		$this->assertSame(1, $driver->connects);

		// Still cooling down: no new connection attempt.
		$driver->isSupported();
		$this->assertSame(1, $driver->connects);

		$driver->expireCooldown();
		$driver->isSupported();
		$this->assertSame(2, $driver->connects);
	}
}

/**
 * Driver whose connection always fails, counting attempts.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class FlakyRedisDriver extends RedisDriver
{
	/**
	 * @var int
	 */
	public int $connects = 0;

	/**
	 * @return void
	 */
	protected function connect(): void
	{
		$this->connects++;
		$this->connected = false;
		$this->retryAt = microtime(true) + static::RECONNECT_COOLDOWN;
	}

	/**
	 * @return void
	 */
	public function expireCooldown(): void
	{
		$this->retryAt = microtime(true) - 1;
	}
}
