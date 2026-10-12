<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Jobs\Drivers\KafkaDriver;
use Proto\Tests\Test;
use Proto\Tests\Unit\Jobs\Fakes\FakeConnection;
use Proto\Tests\Unit\Jobs\Fakes\RecordingJob;

/**
 * RetryFailedJobSettingsTest
 *
 * retryFailedJob() re-queued every job with hard-coded max_retries = 3 and
 * timeout = 300, discarding the job's own settings.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class RetryFailedJobSettingsTest extends Test
{
	/**
	 * This test does not touch the database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * A failed_jobs row.
	 *
	 * @param string $class
	 * @return object
	 */
	private function failedRow(string $class = 'App\\Jobs\\Gone'): object
	{
		return (object) [
			'id' => 'failed_1',
			'job_id' => 'job_1',
			'queue' => 'reports',
			'job_class' => $class,
			'job_name' => 'Example',
			'data' => '{"a":1}',
			'attempts' => 5,
		];
	}

	/**
	 * The original job row's settings are carried over.
	 *
	 * @return void
	 */
	public function testDatabaseRetryKeepsOriginalSettings(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->failedRow()], [(object) ['max_retries' => 7, 'timeout' => 1200]]];

		$this->assertTrue((new DatabaseDriver([], $db))->retryFailedJob('job_1'));

		[, $sql, $params] = $db->calls('fetch')[1];
		$this->assertStringContainsString('SELECT max_retries, timeout FROM jobs WHERE id = ?', $sql);
		$this->assertSame(['job_1'], $params);

		$inserted = $db->calls('insert')[0][2];
		$this->assertSame(7, $inserted->max_retries);
		$this->assertSame(1200, $inserted->timeout);
		$this->assertSame(0, $inserted->attempts);
		$this->assertSame('reports', $inserted->queue);
	}

	/**
	 * When the original row was cleaned up, the job class's defaults apply.
	 *
	 * @return void
	 */
	public function testDatabaseRetryFallsBackToJobClassDefaults(): void
	{
		$db = new FakeConnection();
		$db->fetchResults = [[$this->failedRow(RecordingJob::class)], []];

		$this->assertTrue((new DatabaseDriver([], $db))->retryFailedJob('job_1'));

		$inserted = $db->calls('insert')[0][2];
		$job = new RecordingJob();
		$this->assertSame($job->getMaxRetries(), $inserted->max_retries);
		$this->assertSame($job->getTimeout(), $inserted->timeout);
	}

	/**
	 * The Kafka driver records the settings when a job fails and reuses
	 * them on retry.
	 *
	 * @return void
	 */
	public function testKafkaRetryKeepsOriginalSettings(): void
	{
		$driver = new class extends KafkaDriver
		{
			/**
			 * @var array<int, array>
			 */
			public array $pushed = [];

			/**
			 * Skips the rdkafka extension check.
			 */
			public function __construct()
			{
				$this->config = ['topic_prefix' => 'test-', 'timeout_ms' => 1];
				$this->processingJobs['job_1'] = [
					'payload' => [
						'id' => 'job_1',
						'queue' => 'reports',
						'job_class' => 'App\\Jobs\\Example',
						'job_name' => 'Example',
						'data' => ['a' => 1],
						'attempts' => 2,
						'max_retries' => 7,
						'timeout' => 1200,
					],
					'message' => (object) [],
				];
			}

			/**
			 * @param object $message
			 * @return void
			 */
			protected function commitMessage(object $message): void
			{
			}

			/**
			 * @param array $failedJob
			 * @return void
			 */
			protected function publishToDeadLetterQueue(array $failedJob): void
			{
			}

			/**
			 * @param array $payload
			 * @param string $queue
			 * @param int $delay
			 * @return bool
			 */
			public function push(array $payload, string $queue = 'default', int $delay = 0): bool
			{
				$this->pushed[] = $payload;
				return true;
			}
		};

		$this->assertTrue($driver->markFailed('job_1', 'boom'));
		$this->assertTrue($driver->retryFailedJob('job_1'));

		$this->assertSame(7, $driver->pushed[0]['max_retries']);
		$this->assertSame(1200, $driver->pushed[0]['timeout']);
	}
}
