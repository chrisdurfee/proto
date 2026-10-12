<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Events\Events;
use Proto\Jobs\Events\JobEvent;
use Proto\Jobs\JobQueue;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * JobQueueWorkTest
 *
 * work() used to loop forever with sleep() on an empty queue, so
 * `work($queue, 1)` calls and cron-driven workers never returned, and
 * delayed jobs counted as pending kept routines spinning.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobQueueWorkTest extends Test
{
	/**
	 * This test does not touch the database.
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
		RecordingJob::resetRecords();
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
	 * An empty queue returns immediately with zero processed.
	 *
	 * @return void
	 */
	public function testEmptyQueueReturnsImmediately(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue(['sleep' => 60], $driver);

		$started = microtime(true);
		$this->assertSame(0, $queue->work('default', 1));
		$this->assertLessThan(5.0, microtime(true) - $started);
		$this->assertSame(1, $driver->popCalls);
	}

	/**
	 * Unlimited work() drains every available job, then returns.
	 *
	 * @return void
	 */
	public function testUnlimitedWorkDrainsAvailableJobsThenReturns(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue(['sleep' => 60], $driver);
		$queue->push(RecordingJob::class, 'a');
		$queue->push(RecordingJob::class, 'b');

		$stoppedReason = null;
		$queue->listen('worker.stopped', function (JobEvent $event) use (&$stoppedReason): void
		{
			$stoppedReason = $event->get('reason');
		});

		$this->assertSame(2, $queue->work());
		$this->assertSame(['a', 'b'], RecordingJob::$handled);
		$this->assertSame('empty', $stoppedReason);
	}

	/**
	 * A delayed job is not available yet, so it neither runs nor keeps the
	 * worker waiting.
	 *
	 * @return void
	 */
	public function testDelayedJobDoesNotKeepWorkerAlive(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue(['sleep' => 60], $driver);
		$queue->later(3600, RecordingJob::class, 'later');

		$this->assertSame(0, $queue->work('default', 1));
		$this->assertSame([], RecordingJob::$handled);
		$this->assertSame(['pending' => 1], array_intersect_key($driver->getStats('default'), ['pending' => 1]));
	}

	/**
	 * maxJobs still caps the run when more jobs are available.
	 *
	 * @return void
	 */
	public function testMaxJobsCapsTheRun(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue([], $driver);
		$queue->push(RecordingJob::class, 1);
		$queue->push(RecordingJob::class, 2);

		$this->assertSame(1, $queue->work('default', 1));
		$this->assertSame([1], RecordingJob::$handled);
	}

	/**
	 * Long-running mode waits on an empty queue until stopped.
	 *
	 * @return void
	 */
	public function testLongRunningModeIdlesUntilStopped(): void
	{
		$driver = new InMemoryDriver();
		$queue = new class(['stop_when_empty' => false], $driver) extends JobQueue
		{
			/**
			 * @var int
			 */
			public int $idles = 0;

			/**
			 * Counts idle cycles and stops after three.
			 *
			 * @param int $seconds
			 * @return void
			 */
			protected function idle(int $seconds): void
			{
				if (++$this->idles >= 3)
				{
					$this->stop();
				}
			}
		};

		$this->assertSame(0, $queue->work());
		$this->assertSame(3, $queue->idles);
	}
}
