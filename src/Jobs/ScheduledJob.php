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
	 * @var int $anchor A timestamp the job's run times are aligned to (run
	 * times are `anchor + k * interval`); used for stateless due checks.
	 */
	protected int $anchor;

	/**
	 * Constructor.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param int $delay Initial delay in seconds
	 * @param int|null $interval Recurring interval in seconds
	 * @param int|null $anchor Alignment timestamp for run times (defaults to the first run)
	 */
	public function __construct(JobInterface|string $job, mixed $data = null, ?string $queue = null, int $delay = 0, ?int $interval = null, ?int $anchor = null)
	{
		$this->job = $job;
		$this->data = $data;
		$this->queue = $queue;
		$this->nextRun = time() + $delay;
		$this->interval = $interval;
		$this->anchor = $anchor ?? $this->nextRun;
	}

	/**
	 * Check if a run time falls in the window `(time - window, time]`.
	 *
	 * Unlike isDue(), this needs no state carried between calls, so a
	 * fresh schedule built on every cron tick dispatches each run exactly
	 * once as long as ticks are `window` seconds apart. Recurring jobs run
	 * at `anchor + k * interval`; one-time jobs run at their anchor.
	 *
	 * @param int $time Window end (inclusive)
	 * @param int $window Window length in seconds
	 * @return bool
	 */
	public function isDueWithin(int $time, int $window): bool
	{
		$window = max(1, $window);
		if ($this->interval === null || $this->interval <= 0)
		{
			return $this->anchor <= $time && $this->anchor > $time - $window;
		}

		$offset = ($time - $this->anchor) % $this->interval;
		if ($offset < 0)
		{
			$offset += $this->interval;
		}

		$lastRun = $time - $offset;
		return $lastRun > $time - $window;
	}

	/**
	 * Get the alignment timestamp.
	 *
	 * @return int
	 */
	public function getAnchor(): int
	{
		return $this->anchor;
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
