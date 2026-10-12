<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Automation\Benchmark;
use Proto\Automation\Processes\Jobs\JobSchedulerRoutine;
use Proto\Events\Events;
use Proto\Jobs\JobQueue;
use Proto\Jobs\Scheduler;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

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
			 * @param int $window
			 * @param int|null $now
			 * @return int
			 */
			public function dispatchDue(int $window = 60, ?int $now = null): int
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

	/**
	 * Builds a routine around a queue without Process's constructor.
	 *
	 * @param JobQueue $queue
	 * @param callable|null $schedule Optional schedule() override
	 * @return JobSchedulerRoutine
	 */
	private function routine(JobQueue $queue, ?callable $schedule = null): JobSchedulerRoutine
	{
		return new class($queue, $schedule) extends JobSchedulerRoutine
		{
			/**
			 * @var callable|null
			 */
			private $override;

			/**
			 * @param JobQueue $queue
			 * @param callable|null $schedule
			 */
			public function __construct(JobQueue $queue, ?callable $schedule)
			{
				$this->queue = $queue;
				$this->scheduler = new Scheduler($queue);
				$this->benchmark = new Benchmark();
				$this->override = $schedule;
			}

			/**
			 * @param Scheduler $scheduler
			 * @return void
			 */
			protected function schedule(Scheduler $scheduler): void
			{
				if ($this->override !== null)
				{
					($this->override)($scheduler);
					return;
				}

				parent::schedule($scheduler);
			}
		};
	}

	/**
	 * Runs the routine with error_log silenced.
	 *
	 * @param JobSchedulerRoutine $routine
	 * @return void
	 */
	private function runQuietly(JobSchedulerRoutine $routine): void
	{
		$log = tempnam(sys_get_temp_dir(), 'jobs-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$routine->run();
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}
	}

	/**
	 * Schedules from `jobs.schedule` config are dispatched by a fresh
	 * routine, as on every cron run.
	 *
	 * @return void
	 */
	public function testConfiguredScheduleIsDispatched(): void
	{
		$original = env('jobs');
		setEnv('jobs', (object) [
			'schedule' => [
				(object) ['job' => RecordingJob::class, 'every' => 60, 'data' => ['from' => 'config'], 'queue' => 'cron'],
			],
		]);

		try
		{
			$driver = new InMemoryDriver();
			$this->runQuietly($this->routine(new JobQueue([], $driver)));
		}
		finally
		{
			setEnv('jobs', $original);
		}

		$this->assertCount(1, $driver->jobs);
		$job = array_values($driver->jobs)[0];
		$this->assertSame(RecordingJob::class, $job['job_class']);
		$this->assertSame('cron', $job['queue']);
		$this->assertSame(['from' => 'config'], $job['data']);
	}

	/**
	 * Subclasses can register jobs in code through schedule().
	 *
	 * @return void
	 */
	public function testScheduleHookRegistersJobs(): void
	{
		$driver = new InMemoryDriver();
		$routine = $this->routine(new JobQueue([], $driver), function (Scheduler $scheduler): void
		{
			$scheduler->everyMinute(RecordingJob::class, 'hook');
			$scheduler->daily(RecordingJob::class, 'not-now', null, date('H:i', time() + 7200));
		});

		$this->runQuietly($routine);

		$this->assertSame(['hook'], array_column(array_values($driver->jobs), 'data'));
	}
}
