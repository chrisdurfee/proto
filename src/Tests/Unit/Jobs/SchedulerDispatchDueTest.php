<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Events\Events;
use Proto\Jobs\JobQueue;
use Proto\Jobs\Scheduler;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * SchedulerDispatchDueTest
 *
 * Stateless due checks for cron ticks: a schedule rebuilt on every tick
 * must dispatch each run time exactly once.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class SchedulerDispatchDueTest extends Test
{
	/**
	 * This test does not touch the database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var int A timestamp on a 5-minute boundary.
	 */
	private const BOUNDARY = 1800000000;

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
	 * Counts dispatches from a freshly built schedule at a given time.
	 *
	 * @param callable $register
	 * @param int $now
	 * @return int
	 */
	private function dispatchAt(callable $register, int $now): int
	{
		$scheduler = new Scheduler(new JobQueue([], new InMemoryDriver()));
		$register($scheduler);
		return $scheduler->dispatchDue(60, $now);
	}

	/**
	 * Interval jobs fire in the tick containing their boundary only, even
	 * when cron starts a few seconds late.
	 *
	 * @return void
	 */
	public function testIntervalJobFiresOncePerBoundary(): void
	{
		$register = fn(Scheduler $s) => $s->everyFiveMinutes(RecordingJob::class);

		$this->assertSame(1, $this->dispatchAt($register, self::BOUNDARY + 3));
		$this->assertSame(0, $this->dispatchAt($register, self::BOUNDARY + 61));
		$this->assertSame(0, $this->dispatchAt($register, self::BOUNDARY + 240));
		$this->assertSame(1, $this->dispatchAt($register, self::BOUNDARY + 300));
	}

	/**
	 * Every-minute jobs fire on every tick.
	 *
	 * @return void
	 */
	public function testEveryMinuteFiresEveryTick(): void
	{
		$register = fn(Scheduler $s) => $s->everyMinute(RecordingJob::class);

		$this->assertSame(1, $this->dispatchAt($register, self::BOUNDARY + 10));
		$this->assertSame(1, $this->dispatchAt($register, self::BOUNDARY + 70));
	}

	/**
	 * One-time jobs fire in their own tick and never again, including
	 * when their time is already in the past.
	 *
	 * @return void
	 */
	public function testOneTimeJobFiresOnlyInItsTick(): void
	{
		$at = intdiv(time() + 3600, 60) * 60;
		$register = fn(Scheduler $s) => $s->at(RecordingJob::class, (string) $at);

		$this->assertSame(1, $this->dispatchAt($register, $at + 5));
		$this->assertSame(0, $this->dispatchAt($register, $at + 65));
		$this->assertSame(0, $this->dispatchAt($register, $at - 60));
	}

	/**
	 * Config entries map onto the Scheduler API and bad entries are
	 * rejected.
	 *
	 * @return void
	 */
	public function testLoadRegistersConfiguredEntries(): void
	{
		$scheduler = new Scheduler(new JobQueue([], new InMemoryDriver()));
		$count = $scheduler->load([
			['job' => RecordingJob::class, 'every' => 300],
			(object) ['job' => RecordingJob::class, 'daily' => '03:00'],
			['job' => RecordingJob::class, 'weekly' => [1, '04:30']],
			['job' => RecordingJob::class, 'at' => '2099-01-01 00:00:00'],
		]);

		$this->assertSame(4, $count);
		$jobs = $scheduler->getScheduledJobs();
		$this->assertSame(300, $jobs[0]->getInterval());
		$this->assertSame(86400, $jobs[1]->getInterval());
		$this->assertSame(604800, $jobs[2]->getInterval());
		$this->assertNull($jobs[3]->getInterval());

		$this->expectException(\InvalidArgumentException::class);
		$scheduler->load([['job' => RecordingJob::class]]);
	}
}
