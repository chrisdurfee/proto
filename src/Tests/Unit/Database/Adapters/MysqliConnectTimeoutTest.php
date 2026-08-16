<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database\Adapters;

use Proto\Database\Adapters\Mysqli;
use Proto\Tests\Test;

/**
 * MysqliConnectTimeoutTest
 *
 * Verifies that {@see Mysqli::startConnection()} fails fast against an
 * unreachable host instead of blocking for PHP's default socket
 * timeout (~60s), which would otherwise pin FPM workers.
 *
 * @package Proto\Tests\Unit\Database\Adapters
 */
final class MysqliConnectTimeoutTest extends Test
{
	/**
	 * This test does not touch the real test database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * RFC 5737 TEST-NET-1: reserved for documentation, guaranteed to be
	 * non-routable, so connection attempts fail deterministically
	 * (either a fast timeout or an immediate "no route to host") rather
	 * than depending on a real unreachable-but-sometimes-flaky host.
	 *
	 * @var string
	 */
	private const UNROUTABLE_HOST = '192.0.2.1';

	/**
	 * @return void
	 */
	public function testConnectionToUnroutableHostFailsFastInsteadOfHanging(): void
	{
		$settings = (object)[
			'host' => self::UNROUTABLE_HOST,
			'username' => 'root',
			'password' => '',
			'database' => 'test',
			'port' => 3306,
			'persistent' => false,
			'connectTimeout' => 1
		];

		$db = new Mysqli($settings);

		$start = microtime(true);
		$result = $db->execute('SELECT 1');
		$elapsed = microtime(true) - $start;

		$this->assertFalse(
			$result,
			'A connection to an unroutable test address should fail, not succeed.'
		);

		$this->assertLessThan(
			3.0,
			$elapsed,
			'MySQLi should fail fast via connectTimeout (~1s) instead of blocking ' .
			'for PHP\'s default socket timeout (~60s).'
		);
	}

	/**
	 * The default connect timeout (no explicit `connectTimeout` setting)
	 * should still be short (~5s) rather than unbounded, so a
	 * misconfigured host cannot hang indefinitely by omission.
	 *
	 * @return void
	 */
	public function testDefaultConnectTimeoutIsBounded(): void
	{
		$settings = (object)[
			'host' => self::UNROUTABLE_HOST,
			'username' => 'root',
			'password' => '',
			'database' => 'test',
			'port' => 3306,
			'persistent' => false
		];

		$db = new Mysqli($settings);

		$start = microtime(true);
		$result = $db->execute('SELECT 1');
		$elapsed = microtime(true) - $start;

		$this->assertFalse($result);
		$this->assertLessThan(
			10.0,
			$elapsed,
			'Default connectTimeout should be a short, bounded value (~5s), not ~60s.'
		);
	}
}
