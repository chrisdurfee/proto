<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Drivers\RedisDriver;
use Proto\Tests\Test;

/**
 * RedisDriverBatchTest
 *
 * Covers the batched and atomic primitives the cache layer builds on:
 * multi-get, multi-delete, and the SET NX used as a stampede lock.
 *
 * The driver's `$db` is typed to the Redis extension class, so a fake cannot
 * be assigned to it. These cases override `attempt()` to run the operation
 * against a recording fake instead, which is what exercises the command
 * shaping (index mapping, varargs, option array) under test here.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class RedisDriverBatchTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Builds a driver whose operations run against the supplied fake.
	 *
	 * @param object $fake
	 * @return RedisDriver
	 */
	private function driverFor(object $fake): RedisDriver
	{
		return new class($fake) extends RedisDriver
		{
			/**
			 * @param object $fake
			 */
			public function __construct(private object $fake)
			{
			}

			/**
			 * @param callable $operation
			 * @param mixed $fallback
			 * @return mixed
			 */
			protected function attempt(callable $operation, mixed $fallback): mixed
			{
				return $operation($this->fake);
			}
		};
	}

	/**
	 * Builds a driver whose operations always fail, as when Redis is down.
	 *
	 * @return RedisDriver
	 */
	private function unavailableDriver(): RedisDriver
	{
		return new class extends RedisDriver
		{
			public function __construct()
			{
			}

			/**
			 * @param callable $operation
			 * @param mixed $fallback
			 * @return mixed
			 */
			protected function attempt(callable $operation, mixed $fallback): mixed
			{
				return $fallback;
			}
		};
	}

	/**
	 * mGet returns a positional array, so a miss in the middle must not shift
	 * the remaining values onto the wrong keys.
	 *
	 * @return void
	 */
	public function testGetMultipleMapsValuesToKeysAcrossMisses(): void
	{
		$fake = new class
		{
			public array $requested = [];

			public function mGet(array $keys): array
			{
				$this->requested = $keys;
				return ['first', false, 'third'];
			}
		};

		$result = $this->driverFor($fake)->getMultiple(['a', 'b', 'c']);

		$this->assertSame(['a' => 'first', 'b' => null, 'c' => 'third'], $result);
		$this->assertSame(['a', 'b', 'c'], $fake->requested);
	}

	/**
	 * A cached empty string is a hit, not a miss. Collapsing the two would
	 * make the caller re-run the work it just cached.
	 *
	 * @return void
	 */
	public function testGetMultipleTreatsEmptyStringAsHit(): void
	{
		$fake = new class
		{
			public function mGet(array $keys): array
			{
				return [''];
			}
		};

		$this->assertSame(['a' => ''], $this->driverFor($fake)->getMultiple(['a']));
	}

	/**
	 * @return void
	 */
	public function testGetMultipleDeduplicatesKeys(): void
	{
		$fake = new class
		{
			public array $requested = [];

			public function mGet(array $keys): array
			{
				$this->requested = $keys;
				return array_fill(0, count($keys), 'v');
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertSame(['a' => 'v', 'b' => 'v'], $driver->getMultiple(['a', 'b', 'a']));
		$this->assertSame(['a', 'b'], $fake->requested);
	}

	/**
	 * @return void
	 */
	public function testGetMultipleShortCircuitsOnEmptyInput(): void
	{
		$fake = new class
		{
			public bool $called = false;

			public function mGet(array $keys): array
			{
				$this->called = true;
				return [];
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertSame([], $driver->getMultiple([]));
		$this->assertFalse($fake->called, 'No round trip should be made for an empty key list.');
	}

	/**
	 * A cache outage must read as "nothing cached", never as a hit.
	 *
	 * @return void
	 */
	public function testGetMultipleFallsBackToNullsWhenUnavailable(): void
	{
		$this->assertSame(
			['a' => null, 'b' => null],
			$this->unavailableDriver()->getMultiple(['a', 'b'])
		);
	}

	/**
	 * @return void
	 */
	public function testDeleteMultipleSendsOneCommand(): void
	{
		$fake = new class
		{
			public int $calls = 0;
			public array $requested = [];

			public function del(array $keys): int
			{
				$this->calls++;
				$this->requested = $keys;
				return count($keys);
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertSame(2, $driver->deleteMultiple(['a', 'b', 'a']));
		$this->assertSame(1, $fake->calls, 'Deletes should batch into a single round trip.');
		$this->assertSame(['a', 'b'], $fake->requested);
	}

	/**
	 * @return void
	 */
	public function testDeleteMultipleShortCircuitsOnEmptyInput(): void
	{
		$fake = new class
		{
			public bool $called = false;

			public function del(array $keys): int
			{
				$this->called = true;
				return 0;
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertSame(0, $driver->deleteMultiple([]));
		$this->assertFalse($fake->called);
	}

	/**
	 * The TTL must ride along on the SET. A follow-up EXPIRE can fail after
	 * the SET succeeded, leaving a lock that never releases.
	 *
	 * @return void
	 */
	public function testAddSetsValueAndTtlAtomically(): void
	{
		$fake = new class
		{
			public array $options = [];

			public function set(string $key, string $value, mixed $options = null): bool
			{
				$this->options = $options;
				return true;
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertTrue($driver->add('lock', '1', 30));
		$this->assertSame(['NX', 'EX' => 30], $fake->options);
	}

	/**
	 * @return void
	 */
	public function testAddOmitsExpiryWhenNotRequested(): void
	{
		$fake = new class
		{
			public array $options = [];

			public function set(string $key, string $value, mixed $options = null): bool
			{
				$this->options = $options;
				return true;
			}
		};

		$driver = $this->driverFor($fake);
		$this->assertTrue($driver->add('lock', '1'));
		$this->assertSame(['NX'], $fake->options);
	}

	/**
	 * Redis returns false when NX finds the key present. That caller lost the
	 * race and must not proceed as though it holds the lock.
	 *
	 * @return void
	 */
	public function testAddReturnsFalseWhenKeyExists(): void
	{
		$fake = new class
		{
			public function set(string $key, string $value, mixed $options = null): bool
			{
				return false;
			}
		};

		$this->assertFalse($this->driverFor($fake)->add('lock', '1', 30));
	}

	/**
	 * Failing to acquire is the safe answer when the cache is unreachable:
	 * nobody holds a lock that cannot be released.
	 *
	 * @return void
	 */
	public function testAddReturnsFalseWhenUnavailable(): void
	{
		$this->assertFalse($this->unavailableDriver()->add('lock', '1', 30));
	}
}
