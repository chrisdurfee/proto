<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Events\Events;
use Proto\Jobs\Events\JobEvent;
use Proto\Jobs\JobQueue;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * JobQueueEventsTest
 *
 * `Events` is a Singleton with a protected constructor; JobQueue used to
 * call `new Events()`, so every queue construction fatalled.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobQueueEventsTest extends Test
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
	 * Constructing a queue no longer fatals.
	 *
	 * @return void
	 */
	public function testQueueCanBeConstructed(): void
	{
		$queue = new JobQueue([], new InMemoryDriver());
		$this->assertInstanceOf(JobQueue::class, $queue);
	}

	/**
	 * Listeners registered on the queue receive its events through the
	 * shared Events singleton.
	 *
	 * @return void
	 */
	public function testPushFiresQueuedEventToListeners(): void
	{
		$driver = new InMemoryDriver();
		$queue = new JobQueue([], $driver);

		$received = [];
		$queue->listen('job.queued', function (JobEvent $event) use (&$received): void
		{
			$received[] = $event->get('job_class');
		});

		$this->assertTrue($queue->push(RecordingJob::class, ['a' => 1]));
		$this->assertSame([RecordingJob::class], $received);
		$this->assertCount(1, $driver->jobs);
	}
}
