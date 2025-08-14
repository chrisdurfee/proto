<?php declare(strict_types=1);
namespace Proto\Jobs\Drivers;

/**
 * DriverInterface
 *
 * Interface for job queue drivers.
 *
 * @package Proto\Jobs\Drivers
 */
interface DriverInterface
{
	/**
	 * Push a job onto the queue.
	 *
	 * @param array $payload Job payload
	 * @param string $queue Queue name
	 * @param int $delay Delay in seconds
	 * @return bool
	 */
	public function push(array $payload, string $queue = 'default', int $delay = 0): bool;

	/**
	 * Pop a job from the queue.
	 *
	 * @param string $queue Queue name
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array;

	/**
	 * Mark a job as completed.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function markCompleted(string $jobId): bool;

	/**
	 * Mark a job as failed.
	 *
	 * @param string $jobId Job ID
	 * @param string $error Error message
	 * @return bool
	 */
	public function markFailed(string $jobId, string $error): bool;

	/**
	 * Retry a job.
	 *
	 * @param string $jobId Job ID
	 * @param int $attempts Current attempt count
	 * @param int $delay Delay before retry in seconds
	 * @return bool
	 */
	public function retry(string $jobId, int $attempts, int $delay): bool;

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public function getStats(?string $queue = null): array;

	/**
	 * Clear all jobs from a queue.
	 *
	 * @param string $queue Queue name
	 * @return bool
	 */
	public function clear(string $queue = 'default'): bool;

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of failed jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public function getFailedJobs(int $limit = 50, int $offset = 0): array;

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function retryFailedJob(string $jobId): bool;
}
