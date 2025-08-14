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
		$this->events = new Events();
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
	 * @param string $queue Queue name
	 * @param int $maxJobs Maximum number of jobs to process (0 = unlimited)
	 * @return void
	 */
	public function work(string $queue = 'default', int $maxJobs = 0): void
	{
		$this->isProcessing = true;
		$processedJobs = 0;

		$this->fireEvent('worker.starting', ['queue' => $queue]);

		while ($this->isProcessing)
        {
			$job = $this->pop($queue);
			if ($job === null)
            {
				// No jobs available, sleep and continue
				sleep($this->config['sleep']);
				continue;
			}

			$this->processJob($job);
			$processedJobs++;

			// Check if we've reached the maximum job limit
			if ($maxJobs > 0 && $processedJobs >= $maxJobs)
            {
				break;
			}

			// Memory management
			if (memory_get_usage() > $this->config['memory_limit'] * 1024 * 1024)
            {
				$this->fireEvent('worker.memory_exceeded', ['memory' => memory_get_usage()]);
				break;
			}
		}

		$this->fireEvent('worker.stopped', ['queue' => $queue, 'processed' => $processedJobs]);
		$this->isProcessing = false;
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
		$job = $this->recreateJob($jobData);

		$this->fireEvent('job.failed', array_merge($jobData, [
			'exception' => $exception,
			'attempts' => $attempts
		]));

		// Check if we should retry
		if ($job->shouldRetry($attempts, $exception))
        {
			// Retry the job
			$delay = $job->getRetryDelay() * $attempts; // Exponential backoff
			$this->driver->retry($jobData['id'], $attempts, $delay);
		}
        else
        {
			// Mark as failed and call job's failed method
			$this->driver->markFailed($jobData['id'], $exception->getMessage());
			$job->failed($exception, $jobData['data'] ?? null);
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
