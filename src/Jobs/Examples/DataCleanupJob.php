<?php declare(strict_types=1);
namespace Proto\Jobs\Examples;

use Proto\Jobs\Job;

/**
 * DataCleanupJob
 *
 * Example job for performing data cleanup tasks.
 *
 * @package Proto\Jobs\Examples
 */
class DataCleanupJob extends Job
{
	/**
	 * @var string $queue The queue name for this job
	 */
	protected string $queue = 'maintenance';

	/**
	 * @var int $timeout Job timeout in seconds
	 */
	protected int $timeout = 600; // 10 minutes

	/**
	 * @var int $maxRetries Maximum retry attempts
	 */
	protected int $maxRetries = 1; // Cleanup jobs shouldn't retry much

	/**
	 * Execute the job.
	 *
	 * @param mixed $data The job data
	 * @return mixed The result of the job execution
	 */
	public function handle(mixed $data): mixed
	{
		$cleanupType = $data['type'] ?? 'logs';
		$olderThanDays = $data['older_than_days'] ?? 30;

		error_log("Starting data cleanup: {$cleanupType}, older than {$olderThanDays} days");

		$results = [];

		try {
			switch ($cleanupType) {
				case 'logs':
					$results = $this->cleanupLogs($olderThanDays);
					break;

				case 'temp_files':
					$results = $this->cleanupTempFiles($olderThanDays);
					break;

				case 'old_jobs':
					$results = $this->cleanupOldJobs($olderThanDays);
					break;

				case 'sessions':
					$results = $this->cleanupExpiredSessions($olderThanDays);
					break;

				case 'cache':
					$results = $this->cleanupExpiredCache($olderThanDays);
					break;

				default:
					throw new \InvalidArgumentException("Unknown cleanup type: {$cleanupType}");
			}

			error_log("Data cleanup completed: {$cleanupType}");

			return [
				'status' => 'completed',
				'cleanup_type' => $cleanupType,
				'older_than_days' => $olderThanDays,
				'results' => $results,
				'completed_at' => date('Y-m-d H:i:s')
			];

		} catch (\Exception $e) {
			error_log("Data cleanup failed for {$cleanupType}: " . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Clean up old log files.
	 *
	 * @param int $olderThanDays Days threshold
	 * @return array Cleanup results
	 */
	protected function cleanupLogs(int $olderThanDays): array
	{
		$logDirectory = '/var/log/app'; // Example path
		$cutoffDate = time() - ($olderThanDays * 24 * 60 * 60);

		$deletedFiles = 0;
		$freedSpace = 0;

		// Simulate log cleanup
		sleep(2);

		// In a real implementation, you'd scan the directory and delete old files
		$deletedFiles = rand(5, 20);
		$freedSpace = rand(100000, 500000); // bytes

		error_log("Cleaned up {$deletedFiles} log files, freed {$freedSpace} bytes");

		return [
			'directory' => $logDirectory,
			'deleted_files' => $deletedFiles,
			'freed_space_bytes' => $freedSpace,
			'freed_space_mb' => round($freedSpace / 1024 / 1024, 2)
		];
	}

	/**
	 * Clean up temporary files.
	 *
	 * @param int $olderThanDays Days threshold
	 * @return array Cleanup results
	 */
	protected function cleanupTempFiles(int $olderThanDays): array
	{
		$tempDirectory = sys_get_temp_dir();
		$cutoffDate = time() - ($olderThanDays * 24 * 60 * 60);

		// Simulate temp file cleanup
		sleep(1);

		$deletedFiles = rand(10, 50);
		$freedSpace = rand(50000, 200000);

		error_log("Cleaned up {$deletedFiles} temp files, freed {$freedSpace} bytes");

		return [
			'directory' => $tempDirectory,
			'deleted_files' => $deletedFiles,
			'freed_space_bytes' => $freedSpace,
			'freed_space_mb' => round($freedSpace / 1024 / 1024, 2)
		];
	}

	/**
	 * Clean up old completed jobs.
	 *
	 * @param int $olderThanDays Days threshold
	 * @return array Cleanup results
	 */
	protected function cleanupOldJobs(int $olderThanDays): array
	{
		// In a real implementation, you'd use your JobModel
		// $deletedJobs = JobModel::cleanupCompleted($olderThanDays);

		// Simulate job cleanup
		sleep(1);

		$deletedJobs = rand(100, 500);

		error_log("Cleaned up {$deletedJobs} old completed jobs");

		return [
			'deleted_jobs' => $deletedJobs,
			'table' => 'jobs'
		];
	}

	/**
	 * Clean up expired sessions.
	 *
	 * @param int $olderThanDays Days threshold
	 * @return array Cleanup results
	 */
	protected function cleanupExpiredSessions(int $olderThanDays): array
	{
		// Simulate session cleanup
		sleep(1);

		$deletedSessions = rand(50, 200);

		error_log("Cleaned up {$deletedSessions} expired sessions");

		return [
			'deleted_sessions' => $deletedSessions,
			'table' => 'sessions'
		];
	}

	/**
	 * Clean up expired cache entries.
	 *
	 * @param int $olderThanDays Days threshold
	 * @return array Cleanup results
	 */
	protected function cleanupExpiredCache(int $olderThanDays): array
	{
		// Simulate cache cleanup
		sleep(1);

		$deletedEntries = rand(200, 1000);
		$freedSpace = rand(10000, 100000);

		error_log("Cleaned up {$deletedEntries} cache entries, freed {$freedSpace} bytes");

		return [
			'deleted_entries' => $deletedEntries,
			'freed_space_bytes' => $freedSpace,
			'freed_space_mb' => round($freedSpace / 1024 / 1024, 2)
		];
	}

	/**
	 * Handle job failure.
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void
	{
		$cleanupType = $data['type'] ?? 'unknown';
		error_log("Data cleanup job failed for type {$cleanupType}: " . $exception->getMessage());

		// Notify administrators about cleanup failure
		// This might be critical for system maintenance
	}
}
