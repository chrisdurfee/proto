<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Jobs;

use Proto\Automation\Processes\Routine;
use Proto\Jobs\JobQueue;
use Proto\Jobs\Drivers\DatabaseDriver;

/**
 * Class JobWorkerRoutine
 *
 * Processes jobs from the job queue.
 * This routine can be run to process a specific number of jobs
 * or run continuously as a background worker.
 *
 * @package Proto\Automation\Processes\Jobs
 */
class JobWorkerRoutine extends Routine
{
	/**
	 * @var bool $setLimits Flag to determine if limits should be set.
	 */
	protected bool $setLimits = true;

	/**
	 * @var string $memoryLimit The memory limit for the process.
	 */
	protected string $memoryLimit = '1024M';

	/**
	 * @var int $timeLimit The time limit for the process.
	 */
	protected int $timeLimit = 1800; // 30 minutes for job processing

	/**
	 * @var JobQueue $queue The job queue instance
	 */
	protected JobQueue $queue;

	/**
	 * @var string $queueName The queue name to process
	 */
	protected string $queueName = 'default';

	/**
	 * @var int $maxJobs Maximum number of jobs to process (0 = unlimited)
	 */
	protected int $maxJobs = 0;

	/**
	 * @var bool $verbose Whether to output verbose information
	 */
	protected bool $verbose = false;

	/**
	 * Constructor.
	 *
	 * @param string|null $date The date for the process.
	 * @param string $queueName The queue name to process
	 * @param int $maxJobs Maximum number of jobs to process (0 = unlimited)
	 * @param bool $verbose Whether to enable verbose output
	 */
	public function __construct(?string $date = null, string $queueName = 'default', int $maxJobs = 0, bool $verbose = false)
	{
		parent::__construct($date);

		$this->queueName = $queueName;
		$this->maxJobs = $maxJobs;
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
		try {
			// Initialize job queue with database driver
			$driver = new DatabaseDriver();
			$this->queue = new JobQueue([], $driver);

			if ($this->verbose) {
				echo "Job queue initialized successfully\n";
			}
		} catch (\Exception $e) {
			$message = "Failed to initialize job queue: " . $e->getMessage();
			error_log($message);

			if ($this->verbose) {
				echo "Error: {$message}\n";
			}

			throw $e;
		}
	}

	/**
	 * Performs the job worker routine process.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		try {
			if ($this->verbose) {
				echo "=== Proto Job Worker Routine ===\n";
				echo "Queue: {$this->queueName}\n";
				echo "Max jobs: " . ($this->maxJobs > 0 ? $this->maxJobs : 'unlimited') . "\n";
				echo "Starting at: " . date('Y-m-d H:i:s') . "\n";
			}

			$processedJobs = $this->processJobs();

			if ($this->verbose) {
				echo "Job worker routine completed\n";
				echo "Jobs processed: {$processedJobs}\n";
				echo "Execution time: " . $this->benchmark->getTotal() . " seconds\n\n";
			}

			// Log activity if jobs were processed
			if ($processedJobs > 0) {
				$message = "Proto Job Worker: Processed {$processedJobs} jobs from '{$this->queueName}' queue at " . date('Y-m-d H:i:s');
				error_log($message);
			}

		} catch (\Exception $e) {
			$message = "Proto Job Worker routine error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage();
			error_log($message);

			if ($this->verbose) {
				echo "Error: " . $e->getMessage() . "\n";
				echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
			}

			throw $e;
		}
	}

	/**
	 * Process jobs from the queue.
	 *
	 * @return int Number of jobs processed
	 */
	protected function processJobs(): int
	{
		$processedJobs = 0;
		$startTime = time();

		while ($this->maxJobs === 0 || $processedJobs < $this->maxJobs) {
			// Check if there are jobs available
			$driver = $this->queue->getDriver();
			$stats = $driver->getStats($this->queueName);
			$availableJobs = $stats['pending'] ?? 0;

			if ($availableJobs === 0) {
				if ($this->verbose) {
					echo "No jobs available in '{$this->queueName}' queue\n";
				}
				break;
			}

			// Process one job
			try {
				$this->queue->work($this->queueName, 1);
				$processedJobs++;

				if ($this->verbose) {
					$runtime = time() - $startTime;
					$rate = $runtime > 0 ? round($processedJobs / $runtime, 2) : 0;
					echo "[" . date('Y-m-d H:i:s') . "] Processed job #{$processedJobs} (Rate: {$rate} jobs/sec)\n";
				}

			} catch (\Exception $e) {
				$message = "Error processing job from '{$this->queueName}' queue: " . $e->getMessage();
				error_log($message);

				if ($this->verbose) {
					echo "Job processing error: " . $e->getMessage() . "\n";
				}

				// Continue processing other jobs even if one fails
				break;
			}
		}

		return $processedJobs;
	}

	/**
	 * Show queue statistics if verbose mode is enabled.
	 *
	 * @return void
	 */
	protected function showQueueStats(): void
	{
		try {
			$driver = $this->queue->getDriver();
			$stats = $driver->getStats($this->queueName);

			echo "Queue '{$this->queueName}' stats:\n";
			echo "  Pending: " . $stats['pending'] . "\n";
			echo "  Processing: " . $stats['processing'] . "\n";
			echo "  Completed: " . $stats['completed'] . "\n";
			echo "  Failed: " . $stats['failed'] . "\n";
		} catch (\Exception $e) {
			echo "Failed to retrieve queue stats: " . $e->getMessage() . "\n";
		}
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
	 * Set the queue name to process.
	 *
	 * @param string $queueName
	 * @return self
	 */
	public function setQueueName(string $queueName): self
	{
		$this->queueName = $queueName;
		return $this;
	}

	/**
	 * Set the maximum number of jobs to process.
	 *
	 * @param int $maxJobs
	 * @return self
	 */
	public function setMaxJobs(int $maxJobs): self
	{
		$this->maxJobs = $maxJobs;
		return $this;
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
