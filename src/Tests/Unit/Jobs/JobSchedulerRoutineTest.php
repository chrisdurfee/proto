<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Automation\Benchmark;
use Proto\Automation\Processes\Jobs\JobSchedulerRoutine;
use Proto\Events\Events;
use Proto\Jobs\JobQueue;
use Proto\Jobs\Scheduler;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;

/**
 * JobSchedulerRoutineTest
 *
 * The scheduler routine caught only \Exception, so an \Error (TypeError,
 * fatal-turned-Error) skipped its logging entirely.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobSchedulerRoutineTest extends Test
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
	 * An \Error from the scheduler is logged and rethrown.
	 *
	 * @return void
	 */
	public function testErrorIsLoggedAndRethrown(): void
	{
		$queue = new JobQueue([], new InMemoryDriver());
		$scheduler = new class($queue) extends Scheduler
		{
			/**
			 * @return int
			 */
			public function tick(): int
			{
				throw new \Error('tick exploded');
			}
		};

		$routine = new class($queue, $scheduler) extends JobSchedulerRoutine
		{
			/**
			 * Skips Process's constructor (server limits).
			 *
			 * @param JobQueue $queue
			 * @param Scheduler $scheduler
			 */
			public function __construct(JobQueue $queue, Scheduler $scheduler)
			{
				$this->queue = $queue;
				$this->scheduler = $scheduler;
				$this->benchmark = new Benchmark();
			}

			/**
			 * Process::__destruct() calls die(); skip it in tests.
			 */
			public function __destruct()
			{
			}
		};

		$log = tempnam(sys_get_temp_dir(), 'jobs-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$routine->run();
			$this->fail('Expected the \Error to be rethrown.');
		}
		catch (\Error $e)
		{
			$this->assertSame('tick exploded', $e->getMessage());
			$this->assertStringContainsString('Proto Job Scheduler routine error', (string) file_get_contents($log));
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}
	}
}
