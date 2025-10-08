<?php declare(strict_types=1);
namespace Proto\Jobs;

use Proto\Base;
use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Jobs\Drivers\KafkaDriver;
use Proto\Jobs\Drivers\DriverInterface;

/**
 * Jobs
 *
 * Factory and facade for the Proto Jobs system.
 * Provides convenient static access to job queue and scheduler functionality.
 *
 * @package Proto\Jobs
 */
class Jobs extends Base
{
	/**
	 * @var JobQueue|null $queue Singleton queue instance
	 */
	protected static ?JobQueue $queue = null;

	/**
	 * @var Scheduler|null $scheduler Singleton scheduler instance
	 */
	protected static ?Scheduler $scheduler = null;

	/**
	 * @var array $config Configuration
	 */
	protected static array $config = [];

	/**
	 * Initialize the jobs system with configuration.
	 *
	 * @param array $config Configuration options
	 * @return void
	 */
	public static function configure(array $config = []): void
	{
		static::$config = array_merge([
			'driver' => 'database',
			'connection' => 'default',
		], $config);
	}

	/**
	 * Get the job queue instance.
	 *
	 * @param array|null $config Optional configuration override
	 * @return JobQueue
	 */
	public static function queue(?array $config = null): JobQueue
	{
		if (static::$queue === null || $config !== null) {
			$finalConfig = $config ?? static::$config;
			$driver = static::createDriver($finalConfig);
			static::$queue = new JobQueue($finalConfig, $driver);
		}

		return static::$queue;
	}

	/**
	 * Get the scheduler instance.
	 *
	 * @param array|null $config Optional configuration override
	 * @return Scheduler
	 */
	public static function scheduler(?array $config = null): Scheduler
	{
		if (static::$scheduler === null || $config !== null) {
			$queue = static::queue($config);
			static::$scheduler = new Scheduler($queue);
		}

		return static::$scheduler;
	}

	/**
	 * Create a driver instance based on configuration.
	 *
	 * @param array $config Configuration
	 * @return DriverInterface
	 */
	protected static function createDriver(array $config): DriverInterface
	{
		$driverType = $config['driver'] ?? 'database';

		return match ($driverType) {
			'database' => new DatabaseDriver($config),
			'kafka' => new KafkaDriver($config),
			default => throw new \InvalidArgumentException("Unsupported driver: {$driverType}")
		};
	}

	/**
	 * Push a job onto the queue.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param int|null $delay Delay in seconds
	 * @return bool
	 */
	public static function dispatch(JobInterface|string $job, mixed $data = null, ?string $queue = null, ?int $delay = null): bool
	{
		return static::queue()->push($job, $data, $queue, $delay);
	}

	/**
	 * Push a job onto the queue with a delay.
	 *
	 * @param int $delay Delay in seconds
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return bool
	 */
	public static function dispatchLater(int $delay, JobInterface|string $job, mixed $data = null, ?string $queue = null): bool
	{
		return static::queue()->later($delay, $job, $data, $queue);
	}

	/**
	 * Schedule a job to run at a specific time.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param string $time Time to run
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public static function scheduleAt(JobInterface|string $job, string $time, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return static::scheduler()->at($job, $time, $data, $queue);
	}

	/**
	 * Schedule a job to run after a delay.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param int $delay Delay in seconds
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public static function scheduleIn(JobInterface|string $job, int $delay, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return static::scheduler()->in($job, $delay, $data, $queue);
	}

	/**
	 * Schedule a job to run every X seconds.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param int $interval Interval in seconds
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public static function scheduleEvery(JobInterface|string $job, int $interval, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return static::scheduler()->every($job, $interval, $data, $queue);
	}

	/**
	 * Schedule a job to run daily.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param string $time Time to run
	 * @return ScheduledJob
	 */
	public static function scheduleDaily(JobInterface|string $job, mixed $data = null, ?string $queue = null, string $time = '00:00'): ScheduledJob
	{
		return static::scheduler()->daily($job, $data, $queue, $time);
	}

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name
	 * @return array
	 */
	public static function stats(?string $queue = null): array
	{
		return static::queue()->getStats($queue);
	}

	/**
	 * Clear all jobs from a queue.
	 *
	 * @param string $queue Queue name
	 * @return bool
	 */
	public static function clear(string $queue = 'default'): bool
	{
		return static::queue()->clear($queue);
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public static function failedJobs(int $limit = 50, int $offset = 0): array
	{
		return static::queue()->getFailedJobs($limit, $offset);
	}

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public static function retry(string $jobId): bool
	{
		return static::queue()->retryFailedJob($jobId);
	}

	/**
	 * Work the queue (process jobs).
	 *
	 * @param string $queue Queue name
	 * @param int $maxJobs Maximum number of jobs to process
	 * @return void
	 */
	public static function work(string $queue = 'default', int $maxJobs = 0): void
	{
		static::queue()->work($queue, $maxJobs);
	}

	/**
	 * Stop the queue worker.
	 *
	 * @return void
	 */
	public static function stop(): void
	{
		if (static::$queue !== null) {
			static::$queue->stop();
		}
	}

	/**
	 * Register an event listener.
	 *
	 * @param string $event Event name
	 * @param callable $listener Event listener
	 * @return void
	 */
	public static function listen(string $event, callable $listener): void
	{
		static::queue()->listen($event, $listener);
	}

	/**
	 * Reset the singleton instances (useful for testing).
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		static::$queue = null;
		static::$scheduler = null;
		static::$config = [];
	}
}
