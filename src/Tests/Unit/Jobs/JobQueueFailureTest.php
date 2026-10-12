<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Events\Events;
use Proto\Jobs\JobQueue;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\InMemoryDriver;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * JobQueueFailureTest
 *
 * handleJobFailure() used to call recreateJob() a second time outside any
 * catch, so a job whose class was missing threw again, killed the worker
 * and stayed stuck in `processing`.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class JobQueueFailureTest extends Test
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
	 * Stores a raw job row in the driver.
	 *
	 * @param InMemoryDriver $driver
	 * @param string $id
	 * @param string $class
	 * @param mixed $data
	 * @return void
	 */
	private function storeJob(InMemoryDriver $driver, string $id, string $class, mixed $data = null): void
	{
		$driver->push([
			'id' => $id,
			'job_class' => $class,
			'job_name' => $class,
			'data' => $data,
			'attempts' => 0,
			'max_retries' => 1,
			'timeout' => 0,
		]);
	}

	/**
	 * A job whose class no longer exists is failed permanently with the
	 * reason, and the worker survives.
	 *
	 * @return void
	 */
	public function testMissingJobClassIsMarkedFailedWithoutKillingTheWorker(): void
	{
		$driver = new InMemoryDriver();
		$this->storeJob($driver, 'job_missing', 'Proto\\Tests\\Unit\\Jobs\\Fakes\\DoesNotExistJob');

		$queue = new JobQueue(['sleep' => 0], $driver);
		$queue->work('default', 1);

		$this->assertSame('failed', $driver->statusOf('job_missing'));
		$this->assertStringContainsString('could not be reconstructed', $driver->failed['job_missing']['error']);
		$this->assertStringContainsString('DoesNotExistJob not found', $driver->failed['job_missing']['error']);
	}

	/**
	 * A class that does not implement JobInterface is also failed
	 * permanently rather than retried.
	 *
	 * @return void
	 */
	public function testNonJobClassIsMarkedFailed(): void
	{
		$driver = new InMemoryDriver();
		$this->storeJob($driver, 'job_bad', \ArrayObject::class);

		$queue = new JobQueue(['sleep' => 0], $driver);
		$queue->work('default', 1);

		$this->assertSame('failed', $driver->statusOf('job_bad'));
		$this->assertStringContainsString('must implement JobInterface', $driver->failed['job_bad']['error']);
	}

	/**
	 * A normal job failure past its retry budget still reaches the job's
	 * failed() hook.
	 *
	 * @return void
	 */
	public function testExhaustedJobCallsFailedHook(): void
	{
		$driver = new InMemoryDriver();
		$this->storeJob($driver, 'job_throw', RecordingJob::class, ['throw' => 'boom']);

		$queue = new JobQueue(['sleep' => 0], $driver);
		$queue->work('default', 1);

		$this->assertSame('failed', $driver->statusOf('job_throw'));
		$this->assertSame('boom', $driver->failed['job_throw']['error']);
		$this->assertSame(1, RecordingJob::$failedCalls);
	}
}
