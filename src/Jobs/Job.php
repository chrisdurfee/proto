<?php declare(strict_types=1);
namespace Proto\Jobs;

use Proto\Base;

/**
 * Job
 *
 * Abstract base class for all jobs in the system.
 *
 * @package Proto\Jobs
 */
abstract class Job extends Base implements JobInterface
{
	/**
	 * @var string $name The job name
	 */
	protected string $name;

	/**
	 * @var int $maxRetries Maximum number of retry attempts
	 */
	protected int $maxRetries = 3;

	/**
	 * @var int $retryDelay Delay in seconds before retry
	 */
	protected int $retryDelay = 60;

	/**
	 * @var int $timeout Job timeout in seconds
	 */
	protected int $timeout = 300;

	/**
	 * @var string $queue Queue name for this job
	 */
	protected string $queue = 'default';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->name = $this->name ?? static::class;
	}

	/**
	 * Get the job name/identifier.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the maximum number of retry attempts.
	 *
	 * @return int
	 */
	public function getMaxRetries(): int
	{
		return $this->maxRetries;
	}

	/**
	 * Get the delay in seconds before retry attempts.
	 *
	 * @return int
	 */
	public function getRetryDelay(): int
	{
		return $this->retryDelay;
	}

	/**
	 * Get the job timeout in seconds.
	 *
	 * @return int
	 */
	public function getTimeout(): int
	{
		return $this->timeout;
	}

	/**
	 * Get the queue name for this job.
	 *
	 * @return string
	 */
	public function getQueue(): string
	{
		return $this->queue;
	}

	/**
	 * Set the queue name for this job.
	 *
	 * @param string $queue
	 * @return self
	 */
	public function onQueue(string $queue): self
	{
		$this->queue = $queue;
		return $this;
	}

	/**
	 * Set the maximum retry attempts.
	 *
	 * @param int $maxRetries
	 * @return self
	 */
	public function setMaxRetries(int $maxRetries): self
	{
		$this->maxRetries = $maxRetries;
		return $this;
	}

	/**
	 * Set the retry delay.
	 *
	 * @param int $retryDelay
	 * @return self
	 */
	public function setRetryDelay(int $retryDelay): self
	{
		$this->retryDelay = $retryDelay;
		return $this;
	}

	/**
	 * Set the job timeout.
	 *
	 * @param int $timeout
	 * @return self
	 */
	public function setTimeout(int $timeout): self
	{
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * Set the maximum retry attempts (alias for setMaxRetries).
	 *
	 * @param int $maxRetries
	 * @return self
	 */
	public function retries(int $maxRetries): self
	{
		return $this->setMaxRetries($maxRetries);
	}

	/**
	 * Set the retry delay (alias for setRetryDelay).
	 *
	 * @param int $retryDelay
	 * @return self
	 */
	public function retryAfter(int $retryDelay): self
	{
		return $this->setRetryDelay($retryDelay);
	}

	/**
	 * Set the job timeout (alias for setTimeout).
	 *
	 * @param int $timeout
	 * @return self
	 */
	public function timeout(int $timeout): self
	{
		return $this->setTimeout($timeout);
	}

	/**
	 * Handle job failure (default implementation).
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void
	{
		error(
            "Job {$this->getName()} failed: " . $exception->getMessage(),
            __FILE__,
            __LINE__
        );
	}

	/**
	 * Determine if the job should be retried.
	 *
	 * @param int $attempts Current attempt count
	 * @param \Throwable $exception The exception that caused the failure
	 * @return bool
	 */
	public function shouldRetry(int $attempts, \Throwable $exception): bool
	{
		return $attempts < $this->maxRetries;
	}
}
