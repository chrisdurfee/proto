<?php declare(strict_types=1);
namespace Proto\Jobs;

use Proto\Jobs\JobInterface;

/**
 * ScheduledJob
 *
 * Represents a scheduled job with timing information.
 */
class ScheduledJob
{
	/**
	 * @var JobInterface|string $job Job instance or class name
	 */
	protected JobInterface|string $job;

	/**
	 * @var mixed $data Job data
	 */
	protected mixed $data;

	/**
	 * @var string|null $queue Queue name
	 */
	protected ?string $queue;

	/**
	 * @var int $nextRun Next run timestamp
	 */
	protected int $nextRun;

	/**
	 * @var int|null $interval Recurring interval in seconds
	 */
	protected ?int $interval;

	/**
	 * Constructor.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param int $delay Initial delay in seconds
	 * @param int|null $interval Recurring interval in seconds
	 */
	public function __construct(JobInterface|string $job, mixed $data = null, ?string $queue = null, int $delay = 0, ?int $interval = null)
	{
		$this->job = $job;
		$this->data = $data;
		$this->queue = $queue;
		$this->nextRun = time() + $delay;
		$this->interval = $interval;
	}

	/**
	 * Check if the job is due to run.
	 *
	 * @param int $currentTime Current timestamp
	 * @return bool
	 */
	public function isDue(int $currentTime): bool
	{
		return $currentTime >= $this->nextRun;
	}

	/**
	 * Check if the job is recurring.
	 *
	 * @return bool
	 */
	public function isRecurring(): bool
	{
		return $this->interval !== null;
	}

	/**
	 * Schedule the next run for recurring jobs.
	 *
	 * @return void
	 */
	public function scheduleNext(): void
	{
		if ($this->interval !== null)
        {
			$this->nextRun = time() + $this->interval;
		}
	}

	/**
	 * Get the job.
	 *
	 * @return JobInterface|string
	 */
	public function getJob(): JobInterface|string
	{
		return $this->job;
	}

	/**
	 * Get the job data.
	 *
	 * @return mixed
	 */
	public function getData(): mixed
	{
		return $this->data;
	}

	/**
	 * Get the queue name.
	 *
	 * @return string|null
	 */
	public function getQueue(): ?string
	{
		return $this->queue;
	}

	/**
	 * Get the next run timestamp.
	 *
	 * @return int
	 */
	public function getNextRun(): int
	{
		return $this->nextRun;
	}

	/**
	 * Get the interval.
	 *
	 * @return int|null
	 */
	public function getInterval(): ?int
	{
		return $this->interval;
	}
}
