<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Automation\Benchmark;
use Proto\Automation\Processes\Jobs\JobCleanupRoutine;
use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\FakeConnection;

/**
 * JobCleanupRoutineTest
 *
 * The cleanup routine filtered on a nonexistent `completed_at` column
 * (the jobs table records completion in `processed_at`) and iterated the
 * model's result object as if it were a list of models, so it never
 * deleted anything.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobCleanupRoutineTest extends Test
{
	/**
	 * This test does not touch the real database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Old completed jobs are selected by `processed_at` and old failed
	 * jobs by `failed_at`.
	 *
	 * @return void
	 */
	public function testCleanupUsesRealColumns(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[(object) ['count' => 4]], [(object) ['count' => 2]]];

		$routine = new class(new DatabaseDriver([], $db)) extends JobCleanupRoutine
		{
			/**
			 * Skips Process's constructor (server limits).
			 *
			 * @param DatabaseDriver $driver
			 */
			public function __construct(DatabaseDriver $driver)
			{
				$this->driver = $driver;
				$this->benchmark = new Benchmark();
			}
		};

		$log = tempnam(sys_get_temp_dir(), 'jobs-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$routine->run();
			$this->assertStringContainsString('4 completed, 2 failed', (string) file_get_contents($log));
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}

		$sql = implode("\n", array_column($db->log, 1));
		$this->assertStringNotContainsString('completed_at', $sql);

		$deletes = $db->calls('execute');
		$this->assertCount(2, $deletes);
		$this->assertStringContainsString("DELETE FROM jobs WHERE status = 'completed' AND processed_at < ?", $deletes[0][1]);
		$this->assertStringContainsString('DELETE FROM failed_jobs WHERE failed_at < ?', $deletes[1][1]);

		$cutoff = strtotime($deletes[0][2][0]);
		$this->assertEqualsWithDelta(time() - 7 * 86400, $cutoff, 5);
	}
}
