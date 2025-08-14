<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Jobs;

use Proto\Automation\Processes\Routine;
use Proto\Jobs\Models\JobModel;
use Proto\Jobs\Models\FailedJobModel;

/**
 * Class JobCleanupRoutine
 *
 * Cleans up old completed jobs and failed jobs from the database.
 * This routine helps maintain database performance by removing
 * old job records that are no longer needed.
 *
 * @package Proto\Automation\Processes\Jobs
 */
class JobCleanupRoutine extends Routine
{
	/**
	 * @var bool $setLimits Flag to determine if limits should be set.
	 */
	protected bool $setLimits = true;

	/**
	 * @var string $memoryLimit The memory limit for the process.
	 */
	protected string $memoryLimit = '256M';

	/**
	 * @var int $timeLimit The time limit for the process.
	 */
	protected int $timeLimit = 600; // 10 minutes for cleanup

	/**
	 * @var int $completedJobRetentionDays Number of days to keep completed jobs
	 */
	protected int $completedJobRetentionDays = 7;

	/**
	 * @var int $failedJobRetentionDays Number of days to keep failed jobs
	 */
	protected int $failedJobRetentionDays = 30;

	/**
	 * @var bool $verbose Whether to output verbose information
	 */
	protected bool $verbose = false;

	/**
	 * Constructor.
	 *
	 * @param string|null $date The date for the process.
	 * @param int $completedJobRetentionDays Days to keep completed jobs
	 * @param int $failedJobRetentionDays Days to keep failed jobs
	 * @param bool $verbose Whether to enable verbose output
	 */
	public function __construct(?string $date = null, int $completedJobRetentionDays = 7, int $failedJobRetentionDays = 30, bool $verbose = false)
	{
		parent::__construct($date);

		$this->completedJobRetentionDays = $completedJobRetentionDays;
		$this->failedJobRetentionDays = $failedJobRetentionDays;
		$this->verbose = $verbose;
	}

	/**
	 * Performs the job cleanup routine process.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		try
        {
			if ($this->verbose)
            {
				echo "=== Proto Job Cleanup Routine ===\n";
				echo "Running at: " . date('Y-m-d H:i:s') . "\n";
				echo "Completed job retention: {$this->completedJobRetentionDays} days\n";
				echo "Failed job retention: {$this->failedJobRetentionDays} days\n";
			}

			$totalCleaned = 0;

			// Clean up old completed jobs
			$completedCleaned = $this->cleanupCompletedJobs();
			$totalCleaned += $completedCleaned;

			// Clean up old failed jobs
			$failedCleaned = $this->cleanupFailedJobs();
			$totalCleaned += $failedCleaned;

			if ($this->verbose)
            {
				echo "Job cleanup routine completed\n";
				echo "Total records cleaned: {$totalCleaned}\n";
				echo "Execution time: " . $this->benchmark->getTotal() . " seconds\n\n";
			}

			// Log activity if jobs were cleaned
			if ($totalCleaned > 0)
            {
				$message = "Proto Job Cleanup: Removed {$totalCleaned} old job records ({$completedCleaned} completed, {$failedCleaned} failed) at " . date('Y-m-d H:i:s');
				error_log($message);
			}

		}
        catch (\Exception $e)
        {
			$message = "Proto Job Cleanup routine error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage();
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
	 * Clean up old completed jobs.
	 *
	 * @return int Number of jobs cleaned up
	 */
	protected function cleanupCompletedJobs(): int
	{
		try
        {
			$cutoffDate = date('Y-m-d H:i:s', time() - ($this->completedJobRetentionDays * 24 * 60 * 60));

			if ($this->verbose)
            {
				echo "Cleaning completed jobs older than: {$cutoffDate}\n";
			}

			// Get old completed jobs
			$filter = [
				'status' => 'completed',
				'completed_at <' => $cutoffDate
			];

			$oldJobs = JobModel::all($filter);
			$count = 0;

			foreach ($oldJobs as $job)
            {
				if ($job->delete())
                {
					$count++;
				}
			}

			if ($count > 0)
            {
				if ($this->verbose)
                {
					echo "Cleaned up {$count} completed jobs\n";
				}
			}
            else
            {
				if ($this->verbose)
                {
					echo "No old completed jobs to clean up\n";
				}
			}

			return $count;

		}
        catch (\Exception $e)
        {
			if ($this->verbose)
            {
				echo "Error cleaning completed jobs: " . $e->getMessage() . "\n";
			}
			throw $e;
		}
	}

	/**
	 * Clean up old failed jobs.
	 *
	 * @return int Number of failed jobs cleaned up
	 */
	protected function cleanupFailedJobs(): int
	{
		try
        {
			$cutoffDate = date('Y-m-d H:i:s', time() - ($this->failedJobRetentionDays * 24 * 60 * 60));

			if ($this->verbose)
            {
				echo "Cleaning failed jobs older than: {$cutoffDate}\n";
			}

			// Get old failed jobs
			$filter = [
				'failed_at <' => $cutoffDate
			];

			$oldFailedJobs = FailedJobModel::all($filter);
			$count = 0;

			foreach ($oldFailedJobs as $failedJob)
            {
				if ($failedJob->delete())
                {
					$count++;
				}
			}

			if ($count > 0)
            {
				if ($this->verbose)
                {
					echo "Cleaned up {$count} failed jobs\n";
				}
			}
            else
            {
				if ($this->verbose)
                {
					echo "No old failed jobs to clean up\n";
				}
			}

			return $count;

		}
        catch (\Exception $e)
        {
			if ($this->verbose)
            {
				echo "Error cleaning failed jobs: " . $e->getMessage() . "\n";
			}
			throw $e;
		}
	}

	/**
	 * Set the retention period for completed jobs.
	 *
	 * @param int $days
	 * @return self
	 */
	public function setCompletedJobRetention(int $days): self
	{
		$this->completedJobRetentionDays = $days;
		return $this;
	}

	/**
	 * Set the retention period for failed jobs.
	 *
	 * @param int $days
	 * @return self
	 */
	public function setFailedJobRetention(int $days): self
	{
		$this->failedJobRetentionDays = $days;
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
