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
		if ($timestamp === false)
		{
			throw new \InvalidArgumentException("Invalid schedule time \"{$time}\".");
		}

		$delay = max(0, $timestamp - time());

		// Anchor to the requested time so a stateless tick runs it once.
		$scheduledJob = new ScheduledJob($job, $data, $queue, $delay, null, $timestamp);
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
		// Align to the epoch so stateless ticks agree on run times
		// (e.g. everyFiveMinutes() runs at :00, :05, :10 ...).
		$scheduledJob = new ScheduledJob($job, $data, $queue, 0, $interval, 0);
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
	 * Dispatch every job with a run time in the current tick window.
	 *
	 * Built for cron: each run rebuilds the schedule, so this keeps no
	 * state. The window ends at `now` rounded down to a multiple of
	 * `$window`, which absorbs cron start-up jitter; with ticks `$window`
	 * seconds apart each run time is dispatched once.
	 *
	 * @param int $window Tick period in seconds (60 for a per-minute cron)
	 * @param int|null $now Current timestamp (defaults to time())
	 * @return int Number of jobs queued
	 */
	public function dispatchDue(int $window = 60, ?int $now = null): int
	{
		$window = max(1, $window);
		$now ??= time();
		$tickEnd = intdiv($now, $window) * $window;

		$queuedCount = 0;
		foreach ($this->scheduledJobs as $scheduledJob)
		{
			if (!$scheduledJob->isDueWithin($tickEnd, $window))
			{
				continue;
			}

			$this->queue->push(
				$scheduledJob->getJob(),
				$scheduledJob->getData(),
				$scheduledJob->getQueue(),
				0
			);
			$queuedCount++;
		}

		return $queuedCount;
	}

	/**
	 * Register scheduled jobs from configuration entries.
	 *
	 * Each entry is an array or object with a `job` class name, optional
	 * `data` and `queue`, and one timing key:
	 * - `every`: interval in seconds (epoch-aligned, e.g. 300 = every 5 minutes)
	 * - `daily`: "HH:MM"
	 * - `weekly`: [dayOfWeek (0 = Sunday), "HH:MM"]
	 * - `at`: a date/time string or timestamp (runs once)
	 *
	 * @param iterable $entries Schedule entries
	 * @return int Number of entries registered
	 * @throws \InvalidArgumentException When an entry is missing `job` or a timing key.
	 */
	public function load(iterable $entries): int
	{
		$count = 0;
		foreach ($entries as $entry)
		{
			$entry = json_decode((string) json_encode($entry), true);
			$job = $entry['job'] ?? null;
			if (!is_string($job) || $job === '')
			{
				throw new \InvalidArgumentException('Scheduled job entry is missing "job".');
			}

			$data = $entry['data'] ?? null;
			$queue = $entry['queue'] ?? null;

			if (isset($entry['every']))
			{
				$this->every($job, (int) $entry['every'], $data, $queue);
			}
			elseif (isset($entry['daily']))
			{
				$this->daily($job, $data, $queue, (string) $entry['daily']);
			}
			elseif (isset($entry['weekly']))
			{
				$weekly = (array) $entry['weekly'];
				$this->weekly($job, $data, $queue, (int) ($weekly[0] ?? 0), (string) ($weekly[1] ?? '00:00'));
			}
			elseif (isset($entry['at']))
			{
				$this->at($job, (string) $entry['at'], $data, $queue);
			}
			else
			{
				throw new \InvalidArgumentException("Scheduled job \"{$job}\" needs one of: every, daily, weekly, at.");
			}

			$count++;
		}

		return $count;
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
