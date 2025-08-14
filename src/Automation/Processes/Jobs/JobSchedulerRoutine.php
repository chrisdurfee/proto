<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Jobs;

use Proto\Automation\Processes\Routine;
use Proto\Jobs\JobQueue;
use Proto\Jobs\Scheduler;
use Proto\Jobs\Drivers\DatabaseDriver;

/**
 * Class JobSchedulerRoutine
 *
 * Processes scheduled jobs by running the job scheduler tick.
 * This routine should be run regularly (typically every minute) via cron
 * to dispatch due scheduled jobs to the job queue.
 *
 * @package Proto\Automation\Processes\Jobs
 */
class JobSchedulerRoutine extends Routine
{
	/**
	 * @var bool $setLimits Flag to determine if limits should be set.
	 */
	protected bool $setLimits = true;

	/**
	 * @var string $memoryLimit The memory limit for the process.
	 */
	protected string $memoryLimit = '512M';

	/**
	 * @var int $timeLimit The time limit for the process.
	 */
	protected int $timeLimit = 300; // 5 minutes should be plenty for scheduler

	/**
	 * @var JobQueue $queue The job queue instance
	 */
	protected JobQueue $queue;

	/**
	 * @var Scheduler $scheduler The scheduler instance
	 */
	protected Scheduler $scheduler;

	/**
	 * @var bool $verbose Whether to output verbose information
	 */
	protected bool $verbose = false;

	/**
	 * Constructor.
	 *
	 * @param string|null $date The date for the process.
	 * @param bool $verbose Whether to enable verbose output
	 */
	public function __construct(?string $date = null, bool $verbose = false)
	{
		parent::__construct($date);

		$this->verbose = $verbose;
		$this->initializeJobSystem();
	}

	/**
	 * Initialize the job system components.
	 *
	 * @return void
	 */
	protected function initializeJobSystem(): void
	{
		try
        {
			// Initialize job queue with database driver
			$driver = new DatabaseDriver();
			$this->queue = new JobQueue([], $driver);
			$this->scheduler = new Scheduler($this->queue);

			if ($this->verbose)
            {
				echo "Job system initialized successfully\n";
			}
		}
        catch (\Exception $e)
        {
			$message = "Failed to initialize job system: " . $e->getMessage();
			error_log($message);

			if ($this->verbose)
            {
				echo "Error: {$message}\n";
			}

			throw $e;
		}
	}

	/**
	 * Performs the scheduler routine process.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		try {
			if ($this->verbose)
            {
				echo "=== Proto Job Scheduler Routine ===\n";
				echo "Running at: " . date('Y-m-d H:i:s') . "\n";
				echo "Checking for due scheduled jobs...\n";
			}

			// Process due jobs
			$dueJobs = $this->scheduler->tick();

			if ($this->verbose)
            {
				echo "Due jobs found and dispatched: {$dueJobs}\n";
			}

			// Log activity if jobs were processed
			if ($dueJobs > 0)
            {
				$message = "Proto Job Scheduler: Dispatched {$dueJobs} due jobs at " . date('Y-m-d H:i:s');
				error_log($message);

				if ($this->verbose)
                {
					echo "{$message}\n";
					$this->showQueueStats();
				}
			}
            else
            {
				if ($this->verbose)
                {
					echo "No due jobs found\n";
				}
			}

			if ($this->verbose)
            {
				echo "Scheduler routine completed successfully\n";
				echo "Execution time: " . $this->benchmark->getTotal() . " seconds\n\n";
			}

		}
        catch (\Exception $e)
        {
			$message = "Proto Job Scheduler routine error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage();
			error_log($message);

			if ($this->verbose)
            {
				echo "Error: " . $e->getMessage() . "\n";
				echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
			}

			throw $e;
		}
	}

	/**
	 * Show queue statistics if verbose mode is enabled.
	 *
	 * @return void
	 */
	protected function showQueueStats(): void
	{
		try
        {
			$driver = $this->queue->getDriver();
			$stats = $driver->getStats();

			echo "Queue stats after dispatch:\n";
			echo "  Pending: " . $stats['pending'] . "\n";
			echo "  Processing: " . $stats['processing'] . "\n";
			echo "  Completed: " . $stats['completed'] . "\n";
			echo "  Failed: " . $stats['failed'] . "\n";
		}
        catch (\Exception $e)
        {
			echo "Failed to retrieve queue stats: " . $e->getMessage() . "\n";
		}
	}

	/**
	 * Get the scheduler instance.
	 *
	 * @return Scheduler
	 */
	public function getScheduler(): Scheduler
	{
		return $this->scheduler;
	}

	/**
	 * Get the job queue instance.
	 *
	 * @return JobQueue
	 */
	public function getQueue(): JobQueue
	{
		return $this->queue;
	}

	/**
	 * Enable or disable verbose output.
	 *
	 * @param bool $verbose
	 * @return self
	 */
	public function setVerbose(bool $verbose): self
	{
		$this->verbose = $verbose;
		return $this;
	}
}
