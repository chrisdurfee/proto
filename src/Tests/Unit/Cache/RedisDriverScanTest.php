<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Drivers\RedisDriver;
use Proto\Tests\Test;

/**
 * RedisDriverScanTest
 *
 * @package Proto\Tests\Unit\Cache
 */
final class RedisDriverScanTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Empty SCAN pages must not stop the loop while the cursor is open.
	 *
	 * @return void
	 */
	public function testCollectScanKeysContinuesAfterEmptyPage(): void
	{
		$driver = new class extends RedisDriver
		{
			public function exposeCollect(object $db, string $pattern): array
			{
				return $this->collectScanKeys($db, $pattern);
			}
		};

		$fake = new class
		{
			private int $step = 0;

			public function scan(mixed &$iterator, string $pattern): array
			{
				$this->step++;
				if ($this->step === 1)
				{
					$iterator = 42;
					return [];
				}

				$iterator = 0;
				return ['alpha', 'beta'];
			}
		};

		$this->assertSame(['alpha', 'beta'], $driver->exposeCollect($fake, 'Class:*'));
	}
}
