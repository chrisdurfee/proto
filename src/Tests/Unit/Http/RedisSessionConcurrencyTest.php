<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Http\Session\RedisSession;
use Proto\Tests\Test;

/**
 * RedisSessionConcurrencyTest
 *
 * A session write must not drop keys another request stored after this
 * request loaded the session (parallel SPA calls).
 *
 * @package Proto\Tests\Unit\Http
 */
final class RedisSessionConcurrencyTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testWriteKeepsKeysWrittenByAConcurrentRequest(): void
	{
		$ref = new \ReflectionClass(MemoryRedisSession::class);
		$session = $ref->newInstanceWithoutConstructor();
		MemoryRedisSession::$store = ['session:tok' => json_encode(['user' => 7])];
		(new \ReflectionProperty(RedisSession::class, 'token'))->setValue(null, 'tok');

		$session->exposeLoad();

		// Another request rotates the CSRF token meanwhile.
		MemoryRedisSession::$store['session:tok'] = json_encode(['user' => 7, 'csrf' => 'new']);

		$session->cart = 3;

		$stored = json_decode(MemoryRedisSession::$store['session:tok'], true);
		$this->assertSame('new', $stored['csrf'], 'Concurrent write must survive.');
		$this->assertSame(3, $stored['cart']);
		$this->assertSame(7, $stored['user']);

		unset($session->cart);
		$stored = json_decode(MemoryRedisSession::$store['session:tok'], true);
		$this->assertArrayNotHasKey('cart', $stored);
		$this->assertSame('new', $stored['csrf']);

		(new \ReflectionProperty(RedisSession::class, 'token'))->setValue(null, null);
	}
}

/**
 * RedisSession backed by an in-memory array.
 *
 * @package Proto\Tests\Unit\Http
 */
final class MemoryRedisSession extends RedisSession
{
	/**
	 * @var array<string, string>
	 */
	public static array $store = [];

	/**
	 * @return void
	 */
	public function exposeLoad(): void
	{
		$this->loadData();
	}

	protected function storeGet(string $key): ?string
	{
		return self::$store[$key] ?? null;
	}

	protected function storeSet(string $key, string $value, ?int $expire = null): void
	{
		self::$store[$key] = $value;
	}

	protected function storeExpire(string $key, int $seconds): void
	{
	}

	protected function storeDelete(string $key): bool
	{
		unset(self::$store[$key]);
		return true;
	}
}
