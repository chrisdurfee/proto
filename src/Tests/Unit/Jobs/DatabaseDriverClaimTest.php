<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Events\Events;
use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Jobs\Drivers\ReleasesStaleJobs;
use Proto\Jobs\JobQueue;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\FakeConnection;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;

/**
 * DatabaseDriverClaimTest
 *
 * pop() used a plain SELECT … LIMIT 1 followed by an unconditional UPDATE,
 * so two workers could both run the same job, and a worker that died left
 * its job in `processing` forever.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class DatabaseDriverClaimTest extends Test
{
	/**
	 * This test does not touch the real database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		Events::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		Events::reset();
		parent::tearDown();
	}

	/**
	 * A pending job row.
	 *
	 * @param string $id
	 * @param int $attempts
	 * @param int $maxRetries
	 * @return object
	 */
	private function row(string $id, int $attempts = 0, int $maxRetries = 3): object
	{
		return (object) [
			'id' => $id,
			'queue' => 'default',
			'job_class' => 'App\\Jobs\\Example',
			'job_name' => 'Example',
			'data' => '{"n":1}',
			'attempts' => $attempts,
			'max_retries' => $maxRetries,
			'timeout' => 60,
			'created_at' => '2026-01-01 00:00:00',
			'reserved_at' => '2026-01-01 00:00:00',
		];
	}

	/**
	 * The claim is a guarded UPDATE that also counts the attempt, with no
	 * transaction of its own.
	 *
	 * @return void
	 */
	public function testPopClaimsWithGuardedUpdate(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->row('job_1', 2), $this->row('job_2')]];
		$db->affectedResults = [1];

		$job = (new DatabaseDriver([], $db))->pop('default');

		$this->assertSame('job_1', $job['id']);
		$this->assertSame(2, $job['attempts'], 'attempts reports the count before this run');
		$this->assertSame(['n' => 1], $job['data']);
		$this->assertSame(['fetch', 'execute'], $db->kinds());

		[, $sql, $params] = $db->calls('execute')[0];
		$this->assertStringContainsString("SET status = 'processing'", $sql);
		$this->assertStringContainsString('attempts = attempts + 1', $sql);
		$this->assertStringContainsString("WHERE id = ? AND status = 'pending'", $sql);
		$this->assertSame('job_1', $params[1]);
	}

	/**
	 * When another worker wins the first candidate, the next one is
	 * claimed instead of running the lost job.
	 *
	 * @return void
	 */
	public function testLostClaimMovesToNextCandidate(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->row('job_1'), $this->row('job_2')]];
		$db->affectedResults = [0, 1];

		$job = (new DatabaseDriver([], $db))->pop('default');

		$this->assertSame('job_2', $job['id']);
		$this->assertCount(2, $db->calls('execute'));
	}

	/**
	 * When every candidate keeps being taken, pop() gives up after a
	 * bounded number of rounds.
	 *
	 * @return void
	 */
	public function testPopGivesUpAfterBoundedRounds(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->row('job_1')], [$this->row('job_1')], [$this->row('job_1')], [$this->row('job_1')]];
		$db->affectedResults = [0, 0, 0, 0];

		$this->assertNull((new DatabaseDriver([], $db))->pop('default'));
		$this->assertCount(3, $db->calls('fetch'));
	}

	/**
	 * An empty queue returns null without writing.
	 *
	 * @return void
	 */
	public function testEmptyQueueReturnsNull(): void
	{
		$db = new FakeConnection();
		$this->assertNull((new DatabaseDriver([], $db))->pop('default'));
		$this->assertSame(['fetch'], $db->kinds());
	}

	/**
	 * Stale reservations with retries left go back to pending; exhausted
	 * ones are failed and recorded, each guarded on the reservation.
	 *
	 * @return void
	 */
	public function testReaperReleasesOrFailsStaleJobs(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->row('job_retry', 1, 3), $this->row('job_dead', 3, 3)]];
		$db->affectedResults = [1, 1];

		$released = (new DatabaseDriver([], $db))->releaseStaleJobs(600);

		$this->assertSame(2, $released);
		[, $select, $selectParams] = $db->calls('fetch')[0];
		$this->assertStringContainsString("status = 'processing'", $select);
		$this->assertStringContainsString('GREATEST(COALESCE(timeout, 0), ?)', $select);
		$this->assertSame(600, $selectParams[1]);

		$executes = $db->calls('execute');
		$this->assertStringContainsString("SET status = 'pending'", $executes[0][1]);
		$this->assertStringContainsString("AND status = 'processing' AND reserved_at = ?", $executes[0][1]);
		$this->assertStringContainsString("SET status = 'failed'", $executes[1][1]);

		$inserts = $db->calls('insert');
		$this->assertCount(1, $inserts);
		$this->assertSame('job_dead', $inserts[0][2]->job_id);
		$this->assertSame(['fetch', 'execute', 'begin', 'execute', 'insert', 'commit'], $db->kinds());
	}

	/**
	 * A job that finished between the reaper's read and its write is left
	 * alone.
	 *
	 * @return void
	 */
	public function testReaperLeavesJobsThatFinishedConcurrently(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->row('job_retry', 1, 3), $this->row('job_dead', 3, 3)]];
		$db->affectedResults = [0, 0];

		$this->assertSame(0, (new DatabaseDriver([], $db))->releaseStaleJobs());
		$this->assertSame([], $db->calls('insert'));
	}

	/**
	 * work() runs the reaper with the configured timeout, and not again
	 * within the reap interval.
	 *
	 * @return void
	 */
	public function testWorkRunsTheReaperPeriodically(): void
	{
		$driver = new class extends InMemoryDriver implements ReleasesStaleJobs
		{
			/**
			 * @var array<int, int>
			 */
			public array $reaps = [];

			/**
			 * @param int $timeout
			 * @return int
			 */
			public function releaseStaleJobs(int $timeout = 900): int
			{
				$this->reaps[] = $timeout;
				return 0;
			}
		};

		$queue = new JobQueue(['reserve_timeout' => 120, 'reap_interval' => 3600], $driver);
		$queue->work();
		$queue->work();

		$this->assertSame([120], $driver->reaps);
	}
}
