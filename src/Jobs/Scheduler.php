<?php declare(strict_types=1);
namespace Proto\Jobs;

use Proto\Base;
use Proto\Jobs\JobQueue;
use Proto\Jobs\JobInterface;

/**
 * Scheduler
 *
 * Job scheduler for handling recurring and delayed tasks.
 *
 * @package Proto\Jobs
 */
class Scheduler extends Base
{
	/**
	 * @var array $scheduledJobs Scheduled jobs
	 */
	protected array $scheduledJobs = [];

	/**
	 * @var bool $isRunning Whether the scheduler is running
	 */
	protected bool $isRunning = false;

	/**
	 * Constructor.
	 *
	 * @param JobQueue $queue Job queue instance
	 */
	public function __construct(
        protected JobQueue $queue
    )
	{
		parent::__construct();
	}

	/**
	 * Schedule a job to run at a specific time.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param string $time Time to run (e.g., '2024-01-01 12:00:00' or '+1 hour')
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public function at(JobInterface|string $job, string $time, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		$timestamp = is_numeric($time) ? (int) $time : strtotime($time);
		$delay = max(0, $timestamp - time());

		$scheduledJob = new ScheduledJob($job, $data, $queue, $delay);
		$this->scheduledJobs[] = $scheduledJob;

		return $scheduledJob;
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
	public function in(JobInterface|string $job, int $delay, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		$scheduledJob = new ScheduledJob($job, $data, $queue, $delay);
		$this->scheduledJobs[] = $scheduledJob;

		return $scheduledJob;
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
	public function every(JobInterface|string $job, int $interval, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		$scheduledJob = new ScheduledJob($job, $data, $queue, 0, $interval);
		$this->scheduledJobs[] = $scheduledJob;

		return $scheduledJob;
	}

	/**
	 * Schedule a job to run every minute.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public function everyMinute(JobInterface|string $job, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return $this->every($job, 60, $data, $queue);
	}

	/**
	 * Schedule a job to run every five minutes.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public function everyFiveMinutes(JobInterface|string $job, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return $this->every($job, 300, $data, $queue);
	}

	/**
	 * Schedule a job to run every hour.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @return ScheduledJob
	 */
	public function hourly(JobInterface|string $job, mixed $data = null, ?string $queue = null): ScheduledJob
	{
		return $this->every($job, 3600, $data, $queue);
	}

	/**
	 * Schedule a job to run daily.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param string $time Time to run (e.g., '09:00')
	 * @return ScheduledJob
	 */
	public function daily(JobInterface|string $job, mixed $data = null, ?string $queue = null, string $time = '00:00'): ScheduledJob
	{
		$nextRun = strtotime("today {$time}");
		if ($nextRun <= time())
        {
			$nextRun = strtotime("tomorrow {$time}");
		}

		$delay = $nextRun - time();
		$scheduledJob = new ScheduledJob($job, $data, $queue, $delay, 86400); // 24 hours
		$this->scheduledJobs[] = $scheduledJob;

		return $scheduledJob;
	}

	/**
	 * Schedule a job to run weekly.
	 *
	 * @param JobInterface|string $job Job instance or class name
	 * @param mixed $data Job data
	 * @param string|null $queue Queue name
	 * @param int $dayOfWeek Day of week (0 = Sunday, 1 = Monday, etc.)
	 * @param string $time Time to run (e.g., '09:00')
	 * @return ScheduledJob
	 */
	public function weekly(JobInterface|string $job, mixed $data = null, ?string $queue = null, int $dayOfWeek = 0, string $time = '00:00'): ScheduledJob
	{
		$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$dayName = $days[$dayOfWeek] ?? 'Sunday';

		$nextRun = strtotime("next {$dayName} {$time}");
		if (date('w') == $dayOfWeek && strtotime("today {$time}") > time())
        {
			$nextRun = strtotime("today {$time}");
		}

		$delay = $nextRun - time();
		$scheduledJob = new ScheduledJob($job, $data, $queue, $delay, 604800); // 7 days
		$this->scheduledJobs[] = $scheduledJob;

		return $scheduledJob;
	}

	/**
	 * Run the scheduler (check for due jobs and queue them).
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->isRunning = true;

		while ($this->isRunning)
        {
			$this->processDueJobs();
			sleep(30); // Check every 30 seconds
		}
	}

	/**
	 * Stop the scheduler.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		$this->isRunning = false;
	}

	/**
	 * Process jobs that are due to run.
	 *
	 * @return void
	 */
	protected function processDueJobs(): void
	{
		$currentTime = time();

		foreach ($this->scheduledJobs as $key => $scheduledJob)
        {
			if ($scheduledJob->isDue($currentTime))
            {
				// Queue the job
				$this->queue->push(
					$scheduledJob->getJob(),
					$scheduledJob->getData(),
					$scheduledJob->getQueue(),
					0 // No additional delay since it's due now
				);

				// Handle recurring jobs
				if ($scheduledJob->isRecurring())
                {
					$scheduledJob->scheduleNext();
				}
                else
                {
					// Remove one-time jobs
					unset($this->scheduledJobs[$key]);
				}
			}
		}

		// Re-index array after removing elements
		$this->scheduledJobs = array_values($this->scheduledJobs);
	}

	/**
	 * Get all scheduled jobs.
	 *
	 * @return array
	 */
	public function getScheduledJobs(): array
	{
		return $this->scheduledJobs;
	}

	/**
	 * Clear all scheduled jobs.
	 *
	 * @return void
	 */
	public function clearSchedule(): void
	{
		$this->scheduledJobs = [];
	}

	/**
	 * Run the scheduler once (process due jobs without entering infinite loop).
	 *
	 * @return int Number of jobs queued
	 */
	public function tick(): int
	{
		$queuedCount = 0;
		$currentTime = time();

		foreach ($this->scheduledJobs as $key => $scheduledJob)
        {
			if ($scheduledJob->isDue($currentTime))
            {
				// Queue the job
				$this->queue->push(
					$scheduledJob->getJob(),
					$scheduledJob->getData(),
					$scheduledJob->getQueue(),
					0
				);

				$queuedCount++;

				// Handle recurring jobs
				if ($scheduledJob->isRecurring())
                {
					$scheduledJob->scheduleNext();
				}
                else
                {
					// Remove one-time jobs
					unset($this->scheduledJobs[$key]);
				}
			}
		}

		// Re-index array after removing elements
		$this->scheduledJobs = array_values($this->scheduledJobs);

		return $queuedCount;
	}
}
