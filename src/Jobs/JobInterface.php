<?php declare(strict_types=1);
namespace Proto\Jobs;

/**
 * JobInterface
 *
 * Interface that all jobs must implement.
 *
 * @package Proto\Jobs
 */
interface JobInterface
{
	/**
	 * Execute the job.
	 *
	 * @param mixed $data The job data
	 * @return mixed The result of the job execution
	 */
	public function handle(mixed $data): mixed;

	/**
	 * Get the job name/identifier.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get the maximum number of retry attempts.
	 *
	 * @return int
	 */
	public function getMaxRetries(): int;

	/**
	 * Get the delay in seconds before retry attempts.
	 *
	 * @return int
	 */
	public function getRetryDelay(): int;

	/**
	 * Get the job timeout in seconds.
	 *
	 * @return int
	 */
	public function getTimeout(): int;

	/**
	 * Get the queue name for this job.
	 *
	 * @return string
	 */
	public function getQueue(): string;

	/**
	 * Determine if the job should be retried.
	 *
	 * @param int $attempts Current attempt count
	 * @param \Throwable $exception The exception that caused the failure
	 * @return bool
	 */
	public function shouldRetry(int $attempts, \Throwable $exception): bool;

	/**
	 * Handle job failure.
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void;
}
