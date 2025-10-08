<?php declare(strict_types=1);
namespace Proto\Jobs\Drivers;

use Proto\Base;
use Proto\Database\Database;
use Proto\Database\Adapters\Mysqli;

/**
 * DatabaseDriver
 *
 * Database driver for job queue storage.
 *
 * @package Proto\Jobs\Drivers
 */
class DatabaseDriver extends Base implements DriverInterface
{
	/**
	 * @var array $config Driver configuration
	 */
	protected array $config;

	/**
	 * @var Mysqli|null $connection Database connection
	 */
	protected ?Mysqli $connection = null;

	/**
	 * @var string $jobsTable Jobs table name
	 */
	protected string $jobsTable;

	/**
	 * @var string $failedJobsTable Failed jobs table name
	 */
	protected string $failedJobsTable;

	/**
	 * Constructor.
	 *
	 * @param array $config Driver configuration
	 */
	public function __construct(array $config = [])
	{
		parent::__construct();

		$this->config = array_merge([
			'connection' => 'default',
			'table' => 'jobs',
			'failed_table' => 'failed_jobs',
		], $config);

		$this->jobsTable = $this->config['table'];
		$this->failedJobsTable = $this->config['failed_table'];
	}

	/**
	 * @var int $affectedRows Last affected rows count
	 */
	protected int $affectedRows = 0;

	/**
	 * Get database connection.
	 *
	 * @return Mysqli
	 * @throws \RuntimeException
	 */
	protected function getConnection(): Mysqli
	{
		if ($this->connection === null)
        {
			try
			{
				$this->connection = Database::getConnection($this->config['connection'], true);
			}
			catch (\Exception $e)
			{
				throw new \RuntimeException('Could not establish database connection: ' . $e->getMessage(), 0, $e);
			}
		}

		if ($this->connection === null)
        {
			throw new \RuntimeException('Could not establish database connection');
		}

		return $this->connection;
	}

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
		$db = $this->getConnection();
		$availableAt = $delay > 0 ? date('Y-m-d H:i:s', time() + $delay) : date('Y-m-d H:i:s');

		$data = (object) [
			'id' => $payload['id'],
			'queue' => $queue,
			'job_class' => $payload['job_class'],
			'job_name' => $payload['job_name'],
			'data' => json_encode($payload['data']),
			'attempts' => 0,
			'max_retries' => $payload['max_retries'],
			'timeout' => $payload['timeout'],
			'status' => 'pending',
			'created_at' => $payload['created_at'],
			'available_at' => $availableAt,
			'reserved_at' => null,
			'processed_at' => null,
		];

		return $db->insert($this->jobsTable, $data);
	}

	/**
	 * Pop a job from the queue.
	 *
	 * @param string $queue Queue name
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array
	{
		$db = $this->getConnection();

		// Start transaction to prevent race conditions
		if (!$db->beginTransaction())
        {
			return null;
		}

		try
        {
			// Find the next available job
			$sql = "SELECT * FROM {$this->jobsTable}
					WHERE queue = ? AND status = 'pending' AND available_at <= ?
					ORDER BY created_at ASC
					LIMIT 1";

			$jobs = $db->fetch($sql, [$queue, date('Y-m-d H:i:s')]);
			if (!$jobs || empty($jobs))
            {
				$db->rollback();
				return null;
			}

			$job = $jobs[0];

			// Reserve the job
			$updateData = (object) [
				'id' => $job->id,
				'status' => 'processing',
				'reserved_at' => date('Y-m-d H:i:s'),
			];

			if (!$db->update($this->jobsTable, $updateData))
            {
				$db->rollback();
				return null;
			}

			$db->commit();

			// Return job data
			return [
				'id' => $job->id,
				'queue' => $job->queue,
				'job_class' => $job->job_class,
				'job_name' => $job->job_name,
				'data' => json_decode($job->data, true),
				'attempts' => (int) $job->attempts,
				'max_retries' => (int) $job->max_retries,
				'timeout' => (int) $job->timeout,
				'created_at' => $job->created_at,
				'reserved_at' => $job->reserved_at,
			];

		}
        catch (\Exception $e)
        {
			$db->rollback();
			throw $e;
		}
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function markCompleted(string $jobId): bool
	{
		$db = $this->getConnection();

		$data = (object) [
			'id' => $jobId,
			'status' => 'completed',
			'processed_at' => date('Y-m-d H:i:s'),
		];

		return $db->update($this->jobsTable, $data);
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
		$db = $this->getConnection();

		// Start transaction
		if (!$db->beginTransaction())
        {
			return false;
		}

		try
        {
			// Get the job data
			$sql = "SELECT * FROM {$this->jobsTable} WHERE id = ?";
			$jobs = $db->fetch($sql, [$jobId]);

			if (!$jobs || empty($jobs))
            {
				$db->rollback();
				return false;
			}

			$job = $jobs[0];

			// Update job status
			$updateData = (object) [
				'id' => $jobId,
				'status' => 'failed',
				'processed_at' => date('Y-m-d H:i:s'),
			];

			if (!$db->update($this->jobsTable, $updateData))
            {
				$db->rollback();
				return false;
			}

			// Add to failed jobs table
			$failedJobData = (object) [
				'id' => uniqid('failed_', true),
				'job_id' => $jobId,
				'queue' => $job->queue,
				'job_class' => $job->job_class,
				'job_name' => $job->job_name,
				'data' => $job->data,
				'attempts' => $job->attempts,
				'error' => $error,
				'failed_at' => date('Y-m-d H:i:s'),
			];

			if (!$db->insert($this->failedJobsTable, $failedJobData))
            {
				$db->rollback();
				return false;
			}

			$db->commit();
			return true;

		}
        catch (\Exception $e)
        {
			$db->rollback();
			throw $e;
		}
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
		$db = $this->getConnection();

		$availableAt = date('Y-m-d H:i:s', time() + $delay);

		$data = (object) [
			'id' => $jobId,
			'status' => 'pending',
			'attempts' => $attempts,
			'available_at' => $availableAt,
			'reserved_at' => null,
			'processed_at' => null,
		];

		return $db->update($this->jobsTable, $data);
	}

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public function getStats(?string $queue = null): array
	{
		$db = $this->getConnection();

		// Get counts by status
		$stats = [
			'pending' => 0,
			'processing' => 0,
			'completed' => 0,
			'failed' => 0,
			'total' => 0,
		];

		$sql = "SELECT status, COUNT(*) as count FROM {$this->jobsTable}";
		$params = [];

		if ($queue !== null)
        {
			$sql .= " WHERE queue = ?";
			$params[] = $queue;
		}

		$sql .= " GROUP BY status";

		$results = $db->fetch($sql, $params);
		if ($results)
        {
			foreach ($results as $row)
            {
				$stats[$row->status] = (int) $row->count;
				$stats['total'] += (int) $row->count;
			}
		}

		// Get failed job count
		$failedSql = "SELECT COUNT(*) as count FROM {$this->failedJobsTable}";
		$failedParams = [];

		if ($queue !== null)
        {
			$failedSql .= " WHERE queue = ?";
			$failedParams[] = $queue;
		}

		$failedResults = $db->fetch($failedSql, $failedParams);
		$stats['failed_total'] = $failedResults ? (int) $failedResults[0]->count : 0;

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
		$db = $this->getConnection();

		$sql = "DELETE FROM {$this->jobsTable} WHERE queue = ?";
		return $db->execute($sql, [$queue]);
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of failed jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public function getFailedJobs(int $limit = 50, int $offset = 0): array
	{
		$db = $this->getConnection();

		$sql = "SELECT * FROM {$this->failedJobsTable}
				ORDER BY failed_at DESC
				LIMIT ? OFFSET ?";

		$result = $db->fetch($sql, [$limit, $offset]);
		return $result ?: [];
	}

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function retryFailedJob(string $jobId): bool
	{
		$db = $this->getConnection();

		// Start transaction
		if (!$db->beginTransaction())
        {
			return false;
		}

		try
        {
			// Get the failed job
			$sql = "SELECT * FROM {$this->failedJobsTable} WHERE job_id = ?";
			$failedJobs = $db->fetch($sql, [$jobId]);

			if (!$failedJobs || empty($failedJobs))
            {
				$db->rollback();
				return false;
			}

			$failedJob = $failedJobs[0];

			// Create new job entry
			$newJobData = (object) [
				'id' => uniqid('job_', true),
				'queue' => $failedJob->queue,
				'job_class' => $failedJob->job_class,
				'job_name' => $failedJob->job_name,
				'data' => $failedJob->data,
				'attempts' => 0,
				'max_retries' => 3, // Reset to default
				'timeout' => 300, // Reset to default
				'status' => 'pending',
				'created_at' => date('Y-m-d H:i:s'),
				'available_at' => date('Y-m-d H:i:s'),
				'reserved_at' => null,
				'processed_at' => null,
			];

			if (!$db->insert($this->jobsTable, $newJobData))
            {
				$db->rollback();
				return false;
			}

			// Remove from failed jobs
			if (!$db->delete($this->failedJobsTable, $jobId, 'job_id'))
            {
				$db->rollback();
				return false;
			}

			$db->commit();
			return true;

		}
        catch (\Exception $e)
        {
			$db->rollback();
			throw $e;
		}
	}

	/**
	 * Clean up old completed jobs.
	 *
	 * @param int $olderThanDays Delete jobs older than this many days
	 * @return int Number of jobs deleted
	 */
	public function cleanupCompletedJobs(int $olderThanDays = 7): int
	{
		$db = $this->getConnection();

		$cutoffDate = date('Y-m-d H:i:s', time() - ($olderThanDays * 24 * 60 * 60));

		// First count the jobs to be deleted
		$countSql = "SELECT COUNT(*) as count FROM {$this->jobsTable}
					WHERE status = 'completed' AND processed_at < ?";
		$countResult = $db->fetch($countSql, [$cutoffDate]);
		$count = $countResult ? (int) $countResult[0]->count : 0;
		if ($count > 0)
		{
			$sql = "DELETE FROM {$this->jobsTable}
					WHERE status = 'completed' AND processed_at < ?";
			$db->execute($sql, [$cutoffDate]);
		}

		return $count;
	}

	/**
	 * Clean up old failed jobs.
	 *
	 * @param int $olderThanDays Delete failed jobs older than this many days
	 * @return int Number of jobs deleted
	 */
	public function cleanupFailedJobs(int $olderThanDays = 30): int
	{
		$db = $this->getConnection();

		$cutoffDate = date('Y-m-d H:i:s', time() - ($olderThanDays * 24 * 60 * 60));

		// First count the jobs to be deleted
		$countSql = "SELECT COUNT(*) as count FROM {$this->failedJobsTable} WHERE failed_at < ?";
		$countResult = $db->fetch($countSql, [$cutoffDate]);
		$count = $countResult ? (int) $countResult[0]->count : 0;
		if ($count > 0)
		{
			$sql = "DELETE FROM {$this->failedJobsTable} WHERE failed_at < ?";
			$db->execute($sql, [$cutoffDate]);
		}

		return $count;
	}
}
