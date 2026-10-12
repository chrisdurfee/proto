<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database;

use Proto\Database\Adapters\Mysqli;
use Proto\Tests\Test;

/**
 * NestedTransactionTest
 *
 * Queue code (e.g. the database job driver's pop/markFailed/retry)
 * calls beginTransaction()/commit() around its own work. In MySQL a
 * second START TRANSACTION implicitly COMMITs the open one, so a queue
 * call inside a test's wrapping transaction (or any caller's unit of
 * work) committed it. Nested calls must use savepoints instead.
 *
 * @package Proto\Tests\Unit\Database
 */
final class NestedTransactionTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testNestedBeginAndCommitDoNotEndOuterTransaction(): void
	{
		[$db, $conn] = $this->adapter();

		$this->assertTrue($db->beginTransaction());  // outer (test / caller)
		$this->assertTrue($db->beginTransaction());  // inner (queue call)
		$this->assertTrue($db->commit());            // inner commit

		$this->assertSame(
			['begin_transaction', 'SAVEPOINT proto_sp_1', 'RELEASE SAVEPOINT proto_sp_1'],
			$conn->calls
		);

		$this->assertTrue($db->rollback());          // outer rollback still possible
		$this->assertSame('rollback', end($conn->calls));
		$this->assertNotContains('commit', $conn->calls);
	}

	/**
	 * @return void
	 */
	public function testNestedRollbackOnlyUndoesInnerWork(): void
	{
		[$db, $conn] = $this->adapter();

		$db->beginTransaction();
		$db->beginTransaction();
		$db->rollback();

		$this->assertSame(
			['begin_transaction', 'SAVEPOINT proto_sp_1', 'ROLLBACK TO SAVEPOINT proto_sp_1', 'RELEASE SAVEPOINT proto_sp_1'],
			$conn->calls
		);

		$db->commit();
		$this->assertSame('commit', end($conn->calls));
	}

	/**
	 * A deadlock rolls back the whole transaction server-side, so the
	 * savepoint is gone. The adapter must end the transaction and reset
	 * its level instead of staying stuck at a phantom nesting level.
	 *
	 * @return void
	 */
	public function testLostSavepointAbortsWholeTransaction(): void
	{
		[$db, $conn] = $this->adapter();

		$db->beginTransaction();
		$db->beginTransaction();
		$conn->failSavepoints = true;

		$this->assertFalse($db->rollback());
		$this->assertSame(0, $db->getTransactionLevel());
		$this->assertSame('rollback', end($conn->calls));

		$conn->failSavepoints = false;
		$db->beginTransaction();
		$db->beginTransaction();
		$conn->failSavepoints = true;
		$this->assertFalse($db->commit());
		$this->assertSame(0, $db->getTransactionLevel());
	}

	/**
	 * rollbackToLevel() unwinds only levels above the target.
	 *
	 * @return void
	 */
	public function testRollbackToLevelKeepsOuterTransaction(): void
	{
		[$db, $conn] = $this->adapter();

		$db->beginTransaction();
		$db->beginTransaction();
		$db->beginTransaction();

		$this->assertTrue($db->rollbackToLevel(1));
		$this->assertSame(1, $db->getTransactionLevel());
		$this->assertNotContains('rollback', $conn->calls);
	}

	/**
	 * @return void
	 */
	public function testTopLevelTransactionStillCommits(): void
	{
		[$db, $conn] = $this->adapter();

		$db->beginTransaction();
		$db->commit();

		$this->assertSame(['begin_transaction', 'commit'], $conn->calls);
	}

	/**
	 * @return array{0: Mysqli, 1: RecordingMysqliConnection}
	 */
	private function adapter(): array
	{
		$conn = new RecordingMysqliConnection();
		$db = new class(['host' => 'localhost', 'username' => 'x', 'password' => 'x', 'database' => 'x']) extends Mysqli
		{
			/**
			 * @param object $connection
			 * @return void
			 */
			public function inject(object $connection): void
			{
				$this->setConnection($connection);
				$this->setConnected(true);
			}
		};
		$db->inject($conn);

		return [$db, $conn];
	}
}

/**
 * mysqli double that records transaction statements.
 */
final class RecordingMysqliConnection extends \mysqli
{
	/**
	 * @var array
	 */
	public array $calls = [];

	/**
	 * When set, savepoint statements fail as after a server-side rollback.
	 *
	 * @var bool
	 */
	public bool $failSavepoints = false;

	/**
	 * Skips the real connection.
	 */
	public function __construct()
	{
	}

	/**
	 * @param int $flags
	 * @param string|null $name
	 * @return bool
	 */
	public function begin_transaction(int $flags = 0, ?string $name = null): bool
	{
		$this->calls[] = 'begin_transaction';
		return true;
	}

	/**
	 * @param int $flags
	 * @param string|null $name
	 * @return bool
	 */
	public function commit(int $flags = 0, ?string $name = null): bool
	{
		$this->calls[] = 'commit';
		return true;
	}

	/**
	 * @param int $flags
	 * @param string|null $name
	 * @return bool
	 */
	public function rollback(int $flags = 0, ?string $name = null): bool
	{
		$this->calls[] = 'rollback';
		return true;
	}

	/**
	 * @param string $query
	 * @param int $result_mode
	 * @return \mysqli_result|bool
	 */
	public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): \mysqli_result|bool
	{
		$this->calls[] = $query;
		if ($this->failSavepoints && str_contains($query, 'SAVEPOINT'))
		{
			throw new \mysqli_sql_exception('SAVEPOINT proto_sp_1 does not exist', 1305);
		}
		return true;
	}
}
