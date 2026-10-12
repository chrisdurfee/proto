<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Drivers\Driver;
use Proto\Tests\Test;

/**
 * CacheLockOwnershipTest
 *
 * A stampede lock must only be released by the caller that took it;
 * after expiry another request may own the key.
 *
 * @package Proto\Tests\Unit\Cache
 */
final class CacheLockOwnershipTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testDeleteIfEqualsOnlyRemovesOwnedValue(): void
	{
		$driver = new MemoryDriver();
		$driver->set('k:lock', 'ownerB');

		$this->assertFalse($driver->deleteIfEquals('k:lock', 'ownerA'), 'A stale owner must not delete a newer lock.');
		$this->assertSame('ownerB', $driver->get('k:lock'));

		$this->assertTrue($driver->deleteIfEquals('k:lock', 'ownerB'));
		$this->assertNull($driver->get('k:lock'));
	}
}

/**
 * @package Proto\Tests\Unit\Cache
 */
final class MemoryDriver extends Driver
{
	/**
	 * @var array<string, string>
	 */
	private array $items = [];

	public function isSupported(): bool { return true; }
	public function get(string $key): ?string { return $this->items[$key] ?? null; }
	public function has(string $key): bool { return isset($this->items[$key]); }
	public function incr(string $key): int { $this->items[$key] = (string)((int)($this->items[$key] ?? 0) + 1); return (int)$this->items[$key]; }
	public function keys(string $pattern): ?array { return array_keys($this->items); }
	public function delete(string $key): bool { $had = isset($this->items[$key]); unset($this->items[$key]); return $had; }
	public function set(string $key, string $value, ?int $expire = null): void { $this->items[$key] = $value; }
	public function clear(): bool { $this->items = []; return true; }
}
