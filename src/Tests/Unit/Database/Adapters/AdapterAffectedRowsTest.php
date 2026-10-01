<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database\Adapters;

use Proto\Database\Adapters\Mysqli;
use Proto\Tests\Test;

/**
 * AdapterAffectedRowsTest
 *
 * execute() returns true whenever a statement runs, so guarded writes
 * (`UPDATE … WHERE balance >= ?`) must check affected rows instead.
 * These tests cover executeAffected() without a live database.
 *
 * @package Proto\Tests\Unit\Database\Adapters
 */
final class AdapterAffectedRowsTest extends Test
{
	/**
	 * This test does not touch the real test database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param bool $succeeds
	 * @param int|null $affected
	 * @return Mysqli
	 */
	private function adapter(bool $succeeds, ?int $affected): Mysqli
	{
		return new class($succeeds, $affected) extends Mysqli
		{
			public function __construct(private bool $succeeds, private ?int $affected)
			{
			}

			public function execute(string $sql, array|object $params = []): bool
			{
				$this->setAffectedRows($this->succeeds ? $this->affected : null);
				return $this->succeeds;
			}
		};
	}

	/**
	 * A guard that matched nothing reports 0, not success.
	 *
	 * @return void
	 */
	public function testGuardThatMatchedNothingReturnsZero(): void
	{
		$db = $this->adapter(true, 0);
		$this->assertSame(0, $db->executeAffected('UPDATE t SET n = n - 1 WHERE n >= 1'));
		$this->assertSame(0, $db->getAffectedRows());
	}

	/**
	 * A guard that applied reports the changed row count.
	 *
	 * @return void
	 */
	public function testAppliedGuardReturnsRowCount(): void
	{
		$db = $this->adapter(true, 1);
		$this->assertSame(1, $db->executeAffected('UPDATE t SET n = n - 1 WHERE n >= 1'));
	}

	/**
	 * A failed statement returns false and clears the count.
	 *
	 * @return void
	 */
	public function testFailedStatementReturnsFalse(): void
	{
		$db = $this->adapter(false, 3);
		$this->assertFalse($db->executeAffected('UPDATE t SET n = 1'));
		$this->assertNull($db->getAffectedRows());
	}
}
