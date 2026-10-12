<?php declare(strict_types=1);
namespace Proto\Jobs;

use Proto\Base;
use Proto\Jobs\Drivers\DriverInterface;
use Proto\Jobs\Drivers\DatabaseDriver;
use Proto\Jobs\Events\JobEvent;
use Proto\Events\Events;

/**
 * JobQueue
 *
 * Main queue manager for handling job queuing, processing, and execution.
 *
 * @package Proto\Jobs
 */
class JobQueue extends Base
{
	/**
	 * @var DriverInterface $driver The queue driver
	 */
	protected DriverInterface $driver;

	/**
	 * @var array $config Queue configuration
	 */
	protected array $config;

	/**
	 * @var bool $isProcessing Whether the queue is currently processing
	 */
	protected bool $isProcessing = false;

	/**
	 * @var Events $events Event dispatcher
	 */
	protected Events $events;

	/**
	 * Constructor.
	 *
	 * @param array $config Queue configuration
	 * @param DriverInterface|null $driver Optional custom driver
	 */
	public function __construct(
        array $config = [],
        ?DriverInterface $driver = null
    )
	{
		parent::__construct();

		$this->config = array_merge($this->getDefaultConfig(), $config);
		$this->driver = $driver ?? $this->createDefaultDriver();
		$this->events = Events::getInstance();
	}

	/**
	 * Get default configuration.
	 *
	 * @return array
	 */
	protected function getDefaultConfig(): array
	{
		return [
			'driver' => 'database',
			'connection' => 'default',
			'table' => 'jobs',
			'failed_table' => 'failed_jobs',
			'max_workers' => 1,
			'memory_limit' => 128,
			'timeout' => 60,
			'sleep' => 3,
			'max_tries' => 3,
			'retry_delay' => 60,
			// Return from work() as soon as no job is available right now.
			'stop_when_empty' => true,
			// Long-running mode (stop_when_empty = false): return after this
			// many seconds without a job. 0 waits forever.
			'max_idle' => 0,
			// Return from work() after this many seconds. 0 = no limit.
			'max_time' => 0,
		];
	}

	/**
	 * Create the default driver.
	 *
	 * @return DriverInterface
	 */
	protected function createDefaultDriver(): DriverInterface
	{
		return match ($this->config['driver'])
        {
			'database' => new DatabaseDriver($this->config),
			'kafka' => new Drivers\KafkaDriver($this->config),
			default => throw new \InvalidArgumentException("Unsupported driver: {$this->config['driver']}")
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
	public function push(JobInterface|string $job, mixed $data = null, ?string $queue = null, ?int $delay = null): bool
	{
		if (is_string($job))
        {
			if (!class_exists($job))
			{
				throw new \InvalidArgumentException("Job class \"{$job}\" does not exist.");
			}

			if (!is_subclass_of($job, JobInterface::class))
			{
				throw new \InvalidArgumentException("Job class \"{$job}\" must implement JobInterface.");
			}

			$job = new $job();
		}

		if (!$job instanceof JobInterface)
        {
			throw new \InvalidArgumentException('Job must implement JobInterface');
		}

		$queue = $queue ?? $job->getQueue();
		$delay = $delay ?? 0;

		// Create job payload
		$payload = $this->createJobPayload($job, $data, $queue);

		// Fire before job queued event
		$this->fireEvent('job.queuing', $payload);

		// Add to queue
		$result = $this->driver->push($payload, $queue, $delay);
		if ($result)
        {
			// Fire job queued event
			$this->fireEvent('job.queued', $payload);
		}

		return $result;
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
	public function later(int $delay, JobInterface|string $job, mixed $data = null, ?string $queue = null): bool
	{
		return $this->push($job, $data, $queue, $delay);
	}

	/**
	 * Pop a job from the queue.
	 *
	 * @param string $queue Queue name
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array
	{
		return $this->driver->pop($queue);
	}

	/**
	 * Process jobs from the queue.
	 *
	 * Returns when `$maxJobs` jobs have been processed, when no job is
	 * available right now (default, `stop_when_empty`), after `max_idle`
	 * seconds without work (long-running mode), after `max_time` seconds,
	 * when the memory limit is exceeded, or when stop() is called.
	 *
	 * Delayed and retrying jobs whose `available_at` is in the future are
	 * not available, so they never keep a stop-when-empty worker alive.
	 *
	 * @param string $queue Queue name
	 * @param int $maxJobs Maximum number of jobs to process (0 = unlimited)
	 * @return int Number of jobs processed
	 */
	public function work(string $queue = 'default', int $maxJobs = 0): int
	{
		$this->isProcessing = true;
		$processedJobs = 0;

		$stopWhenEmpty = (bool) ($this->config['stop_when_empty'] ?? true);
		$maxIdle = max(0, (int) ($this->config['max_idle'] ?? 0));
		$maxTime = max(0, (int) ($this->config['max_time'] ?? 0));
		$sleep = max(0, (int) ($this->config['sleep'] ?? 3));

		$startedAt = time();
		$idleSince = $startedAt;
		$reason = 'stopped';

		$this->fireEvent('worker.starting', ['queue' => $queue]);

		while ($this->isProcessing)
        {
			if ($maxTime > 0 && (time() - $startedAt) >= $maxTime)
			{
				$reason = 'max_time';
				break;
			}

			$job = $this->pop($queue);
			if ($job === null)
            {
				if ($stopWhenEmpty)
				{
					$reason = 'empty';
					break;
				}

				if ($maxIdle > 0 && (time() - $idleSince) >= $maxIdle)
				{
					$reason = 'max_idle';
					break;
				}

				// Long-running mode: wait for new or delayed jobs.
				$this->idle($sleep);
				continue;
			}

			$this->processJob($job);
			$processedJobs++;
			$idleSince = time();

			// Check if we've reached the maximum job limit
			if ($maxJobs > 0 && $processedJobs >= $maxJobs)
            {
				$reason = 'max_jobs';
				break;
			}

			// Memory management
			if (memory_get_usage() > $this->config['memory_limit'] * 1024 * 1024)
            {
				$this->fireEvent('worker.memory_exceeded', ['memory' => memory_get_usage()]);
				$reason = 'memory';
				break;
			}
		}

		$this->fireEvent('worker.stopped', [
			'queue' => $queue,
			'processed' => $processedJobs,
			'reason' => $reason
		]);
		$this->isProcessing = false;

		return $processedJobs;
	}

	/**
	 * Waits between polls while the queue is empty.
	 *
	 * @param int $seconds Seconds to sleep
	 * @return void
	 */
	protected function idle(int $seconds): void
	{
		if ($seconds > 0)
		{
			sleep($seconds);
		}
	}

	/**
	 * Stop the queue worker.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		$this->isProcessing = false;
	}

	/**
	 * Process a single job.
	 *
	 * @param array $jobData Job data from queue
	 * @return void
	 */
	protected function processJob(array $jobData): void
	{
		$startTime = microtime(true);

		try
        {
			$this->fireEvent('job.processing', $jobData);

			// Recreate the job instance
			$job = $this->recreateJob($jobData);

			// Set timeout
			set_time_limit($job->getTimeout());

			// Execute the job
			$result = $job->handle($jobData['data'] ?? null);

			// Mark job as completed
			$this->driver->markCompleted($jobData['id']);

			$executionTime = microtime(true) - $startTime;
			$this->fireEvent('job.processed', array_merge($jobData, [
				'result' => $result,
				'execution_time' => $executionTime
			]));
		}
        catch (\Throwable $exception)
        {
			$this->handleJobFailure($jobData, $exception);
		}
	}

	/**
	 * Handle job failure.
	 *
	 * @param array $jobData Job data
	 * @param \Throwable $exception Exception that caused the failure
	 * @return void
	 */
	protected function handleJobFailure(array $jobData, \Throwable $exception): void
	{
		$attempts = (int) ($jobData['attempts'] ?? 0) + 1;

		$this->fireEvent('job.failed', array_merge($jobData, [
			'exception' => $exception,
			'attempts' => $attempts
		]));

		try
		{
			$job = $this->recreateJob($jobData);
		}
		catch (\Throwable $recreateError)
		{
			// A job that cannot be rebuilt (missing or invalid class) will
			// never succeed, so fail it permanently instead of retrying.
			$reason = 'Job could not be reconstructed: ' . $recreateError->getMessage();
			if ($recreateError !== $exception)
			{
				$reason .= ' (original error: ' . $exception->getMessage() . ')';
			}

			$this->driver->markFailed((string) $jobData['id'], $reason);
			return;
		}

		// Check if we should retry
		if ($job->shouldRetry($attempts, $exception))
        {
			// Retry the job
			$delay = $job->getRetryDelay() * $attempts; // Exponential backoff
			$this->driver->retry($jobData['id'], $attempts, $delay);
			return;
		}

		// Mark as failed and call job's failed method
		$this->driver->markFailed($jobData['id'], $exception->getMessage());

		try
		{
			$job->failed($exception, $jobData['data'] ?? null);
		}
		catch (\Throwable $hookError)
		{
			// A throwing failed() hook must not take the worker down.
			error_log("Job {$job->getName()} failed() hook threw: " . $hookError->getMessage());
		}
	}

	/**
	 * Recreate job instance from stored data.
	 *
	 * @param array $jobData Job data
	 * @return JobInterface
	 */
	protected function recreateJob(array $jobData): JobInterface
	{
		$className = $jobData['job_class'];

		if (!class_exists($className))
        {
			throw new \RuntimeException("Job class {$className} not found");
		}

		$job = new $className();

		if (!$job instanceof JobInterface)
        {
			throw new \RuntimeException("Job class {$className} must implement JobInterface");
		}

		return $job;
	}

	/**
	 * Create job payload for storage.
	 *
	 * @param JobInterface $job Job instance
	 * @param mixed $data Job data
	 * @param string $queue Queue name
	 * @return array
	 */
	protected function createJobPayload(JobInterface $job, mixed $data, string $queue): array
	{
		return [
			'id' => uniqid('job_', true),
			'queue' => $queue,
			'job_class' => get_class($job),
			'job_name' => $job->getName(),
			'data' => $data,
			'attempts' => 0,
			'max_retries' => $job->getMaxRetries(),
			'timeout' => $job->getTimeout(),
			'created_at' => date('Y-m-d H:i:s'),
			'available_at' => date('Y-m-d H:i:s'),
		];
	}

	/**
	 * Fire an event.
	 *
	 * @param string $event Event name
	 * @param array $data Event data
	 * @return void
	 */
	protected function fireEvent(string $event, array $data): void
	{
		$jobEvent = new JobEvent($event, $data);
		$this->events->emit($event, $jobEvent);
	}

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public function getStats(?string $queue = null): array
	{
		return $this->driver->getStats($queue);
	}

	/**
	 * Clear all jobs from a queue.
	 *
	 * @param string $queue Queue name
	 * @return bool
	 */
	public function clear(string $queue = 'default'): bool
	{
		return $this->driver->clear($queue);
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of failed jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public function getFailedJobs(int $limit = 50, int $offset = 0): array
	{
		return $this->driver->getFailedJobs($limit, $offset);
	}

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function retryFailedJob(string $jobId): bool
	{
		return $this->driver->retryFailedJob($jobId);
	}

	/**
	 * Register an event listener.
	 *
	 * @param string $event Event name
	 * @param callable $listener Event listener
	 * @return void
	 */
	public function listen(string $event, callable $listener): void
	{
		$this->events->on($event, $listener);
	}

	/**
	 * Get the queue driver.
	 *
	 * @return DriverInterface
	 */
	public function getDriver(): DriverInterface
	{
		return $this->driver;
	}

	/**
	 * Get the configuration.
	 *
	 * @return array
	 */
	public function getConfig(): array
	{
		return $this->config;
	}
}
