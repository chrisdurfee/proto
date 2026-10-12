<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\FakeConnection;

/**
 * DatabaseDriverTransactionTest
 *
 * The database driver always issued its own BEGIN/COMMIT, so calling it
 * inside an open transaction (a caller's unit of work or a test's
 * isolation transaction) committed the outer transaction early.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class DatabaseDriverTransactionTest extends Test
{
	/**
	 * This test does not touch the real database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * A stored job row.
	 *
	 * @return object
	 */
	private function jobRow(): object
	{
		return (object) [
			'id' => 'job_1',
			'queue' => 'default',
			'job_class' => 'App\\Jobs\\Example',
			'job_name' => 'Example',
			'data' => '{"a":1}',
			'attempts' => 3,
		];
	}

	/**
	 * Inside an open transaction markFailed() joins it: no BEGIN, COMMIT
	 * or ROLLBACK of its own.
	 *
	 * @return void
	 */
	public function testMarkFailedJoinsAnOpenTransaction(): void
	{
		$db = new FakeConnection();
		$db->outerTransaction = true;
		$db->fetchResults = [[$this->jobRow()]];

		$driver = new DatabaseDriver([], $db);
		$this->assertTrue($driver->markFailed('job_1', 'boom'));

		$this->assertSame(['fetch', 'update', 'insert'], $db->kinds());
		$this->assertSame('boom', $db->calls('insert')[0][2]->error);
	}

	/**
	 * Without an open transaction markFailed() wraps its writes in one.
	 *
	 * @return void
	 */
	public function testMarkFailedOpensAndCommitsItsOwnTransaction(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->jobRow()]];

		$driver = new DatabaseDriver([], $db);
		$this->assertTrue($driver->markFailed('job_1', 'boom'));

		$this->assertSame(['begin', 'fetch', 'update', 'insert', 'commit'], $db->kinds());
		$this->assertSame(0, $db->getTransactionLevel());
	}

	/**
	 * A missing job rolls back only the driver's own transaction.
	 *
	 * @return void
	 */
	public function testMissingJobRollsBackOwnTransactionOnly(): void
	{
		$db = new FakeConnection();
		$driver = new DatabaseDriver([], $db);
		$this->assertFalse($driver->markFailed('nope', 'boom'));
		$this->assertSame(['begin', 'fetch', 'rollback'], $db->kinds());

		$outer = new FakeConnection();
		$outer->outerTransaction = true;
		$driver = new DatabaseDriver([], $outer);
		$this->assertFalse($driver->markFailed('nope', 'boom'));
		$this->assertSame(['fetch'], $outer->kinds());
	}

	/**
	 * retryFailedJob() also joins an open transaction.
	 *
	 * @return void
	 */
	public function testRetryFailedJobJoinsAnOpenTransaction(): void
	{
		$db = new FakeConnection();
		$db->outerTransaction = true;
		$db->fetchResults = [[$this->jobRow()]];

		$driver = new DatabaseDriver([], $db);
		$this->assertTrue($driver->retryFailedJob('job_1'));
		$this->assertSame(['fetch', 'insert', 'delete'], $db->kinds());
	}
}
