<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Automation\Benchmark;
use Proto\Automation\Processes\Jobs\JobWorkerRoutine;
use Proto\Events\Events;
use Proto\Jobs\Drivers\DriverInterface;
use Proto\Jobs\JobQueue;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * JobWorkerRoutineTest
 *
 * The worker routine decided whether to keep going from the `pending`
 * stat, which includes delayed/retrying jobs, then called work(), which
 * blocked forever when only delayed jobs remained.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobWorkerRoutineTest extends Test
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
	 * Builds a routine around a driver without running Process's
	 * constructor (server limits) or destructor (die()).
	 *
	 * @param DriverInterface $driver
	 * @param int $maxJobs
	 * @return JobWorkerRoutine
	 */
	private function routine(DriverInterface $driver, int $maxJobs = 0): JobWorkerRoutine
	{
		return new class($driver, $maxJobs) extends JobWorkerRoutine
		{
			/**
			 * @param DriverInterface $driver
			 * @param int $maxJobs
			 */
			public function __construct(DriverInterface $driver, int $maxJobs)
			{
				$this->queue = new JobQueue(['sleep' => 60], $driver);
				$this->maxJobs = $maxJobs;
				$this->benchmark = new Benchmark();
			}

			/**
			 * Exposes processJobs().
			 *
			 * @return int
			 */
			public function runJobs(): int
			{
				return $this->processJobs();
			}

			/**
			 * Process::__destruct() calls die(); skip it in tests.
			 */
			public function __destruct()
			{
			}
		};
	}

	/**
	 * Ready jobs are processed and delayed ones are left alone without
	 * blocking the routine.
	 *
	 * @return void
	 */
	public function testDelayedJobsDoNotBlockTheRoutine(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue([], $driver);
		$queue->push(RecordingJob::class, 'now');
		$queue->later(3600, RecordingJob::class, 'later');

		$started = microtime(true);
		$this->assertSame(1, $this->routine($driver)->runJobs());
		$this->assertLessThan(5.0, microtime(true) - $started);
		$this->assertSame(['now'], RecordingJob::$handled);
	}

	/**
	 * An \Error (not an \Exception) from the driver is caught and logged
	 * by the routine's per-job handler instead of escaping it.
	 *
	 * @return void
	 */
	public function testErrorFromDriverIsCaughtAndLogged(): void
	{
		$driver = new class extends InMemoryDriver
		{
			/**
			 * @param string $queue
			 * @return array|null
			 */
			public function pop(string $queue = 'default'): ?array
			{
				throw new \Error('driver exploded');
			}
		};

		$log = tempnam(sys_get_temp_dir(), 'jobs-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$this->assertSame(0, $this->routine($driver)->runJobs());
			$this->assertStringContainsString('driver exploded', (string) file_get_contents($log));
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}
	}
}
