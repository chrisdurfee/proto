<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs\Fakes;

use Proto\Jobs\Drivers\DriverInterface;

/**
 * InMemoryDriver
 *
 * Array-backed queue driver for unit tests. Mirrors the database driver's
 * status lifecycle (pending -> processing -> completed/failed) and honours
 * `available_at` so delayed jobs are not handed out early.
 *
 * @package Proto\Tests\Unit\Jobs\Fakes
 */
class InMemoryDriver implements DriverInterface
{
	/**
	 * @var array<string, array> $jobs Stored jobs keyed by id.
	 */
	public array $jobs = [];

	/**
	 * @var array<string, array> $failed Failed job records keyed by job id.
	 */
	public array $failed = [];

	/**
	 * @var int $popCalls Number of pop() calls.
	 */
	public int $popCalls = 0;

	/**
	 * Push a job onto the queue.
	 *
	 * @param array $payload Job payload
	 * @param string $queue Queue name
	 * @param int $delay Delay in seconds
	 * @return bool
	 */
	public function push(array $payload, string $queue = 'default', int $delay = 0): bool
	{
		$payload['queue'] = $queue;
		$payload['status'] = 'pending';
		$payload['available_at'] = time() + $delay;
		$this->jobs[$payload['id']] = $payload;
		return true;
	}

	/**
	 * Pop the next available job.
	 *
	 * @param string $queue Queue name
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array
	{
		$this->popCalls++;
		foreach ($this->jobs as $id => $job)
		{
			if ($job['queue'] !== $queue || $job['status'] !== 'pending' || $job['available_at'] > time())
			{
				continue;
			}

			$this->jobs[$id]['status'] = 'processing';
			return $job;
		}

		return null;
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function markCompleted(string $jobId): bool
	{
		$this->jobs[$jobId]['status'] = 'completed';
		return true;
	}

	/**
	 * Mark a job as failed.
	 *
	 * @param string $jobId Job ID
	 * @param string $error Error message
	 * @return bool
	 */
	public function markFailed(string $jobId, string $error): bool
	{
		$this->jobs[$jobId]['status'] = 'failed';
		$this->failed[$jobId] = ['job_id' => $jobId, 'error' => $error];
		return true;
	}

	/**
	 * Retry a job.
	 *
	 * @param string $jobId Job ID
	 * @param int $attempts Current attempt count
	 * @param int $delay Delay before retry in seconds
	 * @return bool
	 */
	public function retry(string $jobId, int $attempts, int $delay): bool
	{
		$this->jobs[$jobId]['status'] = 'pending';
		$this->jobs[$jobId]['attempts'] = $attempts;
		$this->jobs[$jobId]['available_at'] = time() + $delay;
		return true;
	}

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name
	 * @return array
	 */
	public function getStats(?string $queue = null): array
	{
		$stats = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'total' => 0];
		foreach ($this->jobs as $job)
		{
			if ($queue !== null && $job['queue'] !== $queue)
			{
				continue;
			}

			$stats[$job['status']]++;
			$stats['total']++;
		}

		return $stats;
	}

	/**
	 * Clear all jobs from a queue.
	 *
	 * @param string $queue Queue name
	 * @return bool
	 */
	public function clear(string $queue = 'default'): bool
	{
		$this->jobs = array_filter($this->jobs, fn(array $job): bool => $job['queue'] !== $queue);
		return true;
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Limit
	 * @param int $offset Offset
	 * @return array
	 */
	public function getFailedJobs(int $limit = 50, int $offset = 0): array
	{
		return array_slice(array_values($this->failed), $offset, $limit);
	}

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function retryFailedJob(string $jobId): bool
	{
		return false;
	}

	/**
	 * Returns the status of a stored job.
	 *
	 * @param string $jobId Job ID
	 * @return string|null
	 */
	public function statusOf(string $jobId): ?string
	{
		return $this->jobs[$jobId]['status'] ?? null;
	}
}
