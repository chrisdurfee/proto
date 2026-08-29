<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Cache;
use Proto\Tests\Test;

/**
 * CacheEnabledTest
 *
 * Covers the `cache.enabled` override that lets an environment exercise the
 * real cache path even when the environment name would disable it.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class CacheEnabledTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var mixed
	 */
	private mixed $originalCache = null;

	/**
	 * @var mixed
	 */
	private mixed $originalEnv = null;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->originalCache = env('cache');
		$this->originalEnv = env('env');
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		setEnv('cache', $this->originalCache);
		setEnv('env', $this->originalEnv);
		$this->resetMemoizedEnv();
		parent::tearDown();
	}

	/**
	 * Cache memoizes the environment name for the worker's lifetime, so it
	 * has to be cleared between cases that change it.
	 *
	 * @return void
	 */
	private function resetMemoizedEnv(): void
	{
		(new \ReflectionClass(Cache::class))
			->getProperty('env')
			->setValue(null, null);
	}

	/**
	 * @param string $env
	 * @param mixed $enabled
	 * @return void
	 */
	private function configure(string $env, mixed $enabled = null): void
	{
		$cache = (object)['driver' => 'RedisDriver'];
		if ($enabled !== null)
		{
			$cache->enabled = $enabled;
		}

		setEnv('cache', $cache);
		setEnv('env', $env);
		$this->resetMemoizedEnv();
	}

	/**
	 * @return void
	 */
	public function testDisabledByDefaultInDev(): void
	{
		$this->configure('dev');
		$this->assertFalse(Cache::isEnabled());
	}

	/**
	 * @return void
	 */
	public function testEnabledByDefaultOutsideDev(): void
	{
		$this->configure('prod');
		$this->assertTrue(Cache::isEnabled());
	}

	/**
	 * The point of the override: exercise the real cache path locally so a
	 * keying or invalidation bug surfaces in development.
	 *
	 * @return void
	 */
	public function testExplicitTrueOverridesDev(): void
	{
		$this->configure('dev', true);
		$this->assertTrue(Cache::isEnabled());
	}

	/**
	 * @return void
	 */
	public function testExplicitFalseOverridesProd(): void
	{
		$this->configure('prod', false);
		$this->assertFalse(Cache::isEnabled());
	}

	/**
	 * @return void
	 */
	public function testDisabledCacheIsNeverSupported(): void
	{
		$this->configure('prod', false);
		$this->assertFalse(Cache::isSupported());
	}
}
