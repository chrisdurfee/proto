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
class DatabaseDriver extends Base implements DriverInterface, ReleasesStaleJobs
{
	/**
	 * @var int CLAIM_BATCH Candidates read per claim round in pop().
	 */
	protected const CLAIM_BATCH = 5;

	/**
	 * @var int CLAIM_ROUNDS Re-reads in pop() when every candidate was taken by another worker.
	 */
	protected const CLAIM_ROUNDS = 3;

	/**
	 * @var int REAP_BATCH Stale jobs handled per releaseStaleJobs() call.
	 */
	protected const REAP_BATCH = 100;

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
	 * @param Mysqli|null $connection Optional connection (defaults to the configured one)
	 */
	public function __construct(array $config = [], ?Mysqli $connection = null)
	{
		parent::__construct();

		$this->connection = $connection;

		$this->config = array_merge([
			'connection' => 'default',
			'table' => 'jobs',
			'failed_table' => 'failed_jobs',
		], $config);

		$this->jobsTable = $this->sanitizeTableName($this->config['table']);
		$this->failedJobsTable = $this->sanitizeTableName($this->config['failed_table']);
	}

	/**
	 * Validates and returns a safe table name (alphanumeric and underscores only).
	 *
	 * Table names are interpolated directly into SQL strings because the
	 * MySQLi driver does not support parameterized identifiers, so we must
	 * ensure the name contains only safe characters.
	 *
	 * @param string $name Raw table name from configuration.
	 * @return string Validated table name.
	 * @throws \InvalidArgumentException When the name contains unsafe characters.
	 */
	protected function sanitizeTableName(string $name): string
	{
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $name))
		{
			throw new \InvalidArgumentException(
				"Invalid job table name \"{$name}\". Only alphanumeric characters and underscores are allowed."
			);
		}

		return $name;
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
	 * Runs a callback inside a transaction.
	 *
	 * Committed when the callback returns anything but false, rolled back
	 * on false or on a thrown error. Inside an open transaction (a caller's
	 * unit of work, or a test's isolation transaction) this is a savepoint,
	 * so a failed callback undoes only its own writes and never ends the
	 * outer transaction.
	 *
	 * @param callable(Mysqli): mixed $callback Unit of work.
	 * @return mixed The callback's result, or false when the transaction could not start or commit.
	 */
	protected function transactional(callable $callback): mixed
	{
		$db = $this->getConnection();
		if (!$db->beginTransaction())
		{
			return false;
		}

		try
		{
			$result = $callback($db);
		}
		catch (\Throwable $e)
		{
			$db->rollback();
			throw $e;
		}

		if ($result === false)
		{
			$db->rollback();
			return false;
		}

		return $db->commit() ? $result : false;
	}

	/**
	 * Current transaction nesting level of the driver's connection.
	 *
	 * @return int
	 */
	public function transactionLevel(): int
	{
		return $this->getConnection()->getTransactionLevel();
	}

	/**
	 * Rolls back anything above `$level` left open on the connection
	 * (a job handler that began a transaction and threw). Returns how
	 * many levels were open beyond it.
	 *
	 * @param int $level
	 * @return int
	 */
	public function rollbackLeakedTransactions(int $level): int
	{
		$db = $this->getConnection();
		$leaked = $db->getTransactionLevel() - $level;
		if ($leaked <= 0)
		{
			return 0;
		}

		if (!$db->rollbackToLevel($level))
		{
			$db->abortTransaction();
		}

		return $leaked;
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
	 * Claims are atomic without a transaction: candidates are read, then
	 * each is claimed with a guarded `UPDATE … WHERE id = ? AND status =
	 * 'pending'`. Only the worker whose UPDATE changed the row owns the
	 * job; a worker that lost the race moves on to the next candidate.
	 *
	 * The returned `attempts` is the count before this run; the stored
	 * count is incremented by the claim so a crashed run still counts
	 * against the retry budget when the reaper recovers it.
	 *
	 * @param string $queue Queue name
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array
	{
		$db = $this->getConnection();

		$select = "SELECT * FROM {$this->jobsTable}
				WHERE queue = ? AND status = 'pending' AND available_at <= ?
				ORDER BY created_at ASC
				LIMIT " . static::CLAIM_BATCH;

		$claim = "UPDATE {$this->jobsTable}
				SET status = 'processing', reserved_at = ?, attempts = attempts + 1
				WHERE id = ? AND status = 'pending' AND available_at <= ?";

		for ($round = 0; $round < static::CLAIM_ROUNDS; $round++)
		{
			$now = date('Y-m-d H:i:s');
			$jobs = $db->fetch($select, [$queue, $now]);
			if (!$jobs)
            {
				return null;
			}

			foreach ($jobs as $job)
			{
				$claimed = $db->executeAffected($claim, [$now, $job->id, $now]);
				if ($claimed === 1)
				{
					return $this->formatJob($job, $now);
				}
			}

			// Every candidate was claimed by another worker; look again.
		}

		return null;
	}

	/**
	 * Converts a claimed job row into the queue's job array.
	 *
	 * @param object $job Job row as read before the claim
	 * @param string $reservedAt Reservation timestamp
	 * @return array
	 */
	protected function formatJob(object $job, string $reservedAt): array
	{
		return [
			'id' => $job->id,
			'queue' => $job->queue,
			'job_class' => $job->job_class,
			'job_name' => $job->job_name,
			'data' => json_decode((string) $job->data, true),
			'attempts' => (int) $job->attempts,
			'max_retries' => (int) $job->max_retries,
			'timeout' => (int) $job->timeout,
			'created_at' => $job->created_at,
			'reserved_at' => $reservedAt,
		];
	}

	/**
	 * Returns jobs stuck in `processing` to the queue.
	 *
	 * A worker that dies mid-job (fatal, OOM, kill) leaves its row in
	 * `processing` forever. Rows reserved longer than
	 * max(`$timeout`, the job's own timeout) are released: back to
	 * `pending` when retries remain, otherwise marked failed and copied to
	 * the failed jobs table. Each release is guarded on the row still
	 * being reserved at the same time, so a worker finishing concurrently
	 * wins.
	 *
	 * @param int $timeout Seconds a reservation may last (default 15 minutes)
	 * @return int Number of jobs released or failed
	 */
	public function releaseStaleJobs(int $timeout = 900): int
	{
		$db = $this->getConnection();
		$now = date('Y-m-d H:i:s');

		$sql = "SELECT * FROM {$this->jobsTable}
				WHERE status = 'processing' AND reserved_at IS NOT NULL
				AND reserved_at < DATE_SUB(?, INTERVAL GREATEST(COALESCE(timeout, 0), ?) SECOND)
				ORDER BY reserved_at ASC
				LIMIT " . static::REAP_BATCH;

		$stale = $db->fetch($sql, [$now, max(1, $timeout)]);
		if (!$stale)
		{
			return 0;
		}

		$released = 0;
		foreach ($stale as $job)
		{
			if ((int) $job->attempts < (int) $job->max_retries)
			{
				$affected = $db->executeAffected(
					"UPDATE {$this->jobsTable}
					SET status = 'pending', reserved_at = NULL, available_at = ?
					WHERE id = ? AND status = 'processing' AND reserved_at = ?",
					[$now, $job->id, $job->reserved_at]
				);

				$released += $affected === 1 ? 1 : 0;
				continue;
			}

			$error = "Job exceeded its reservation timeout after {$job->attempts} attempt(s); the worker likely died.";
			$failed = $this->transactional(function (Mysqli $db) use ($job, $now, $error): bool
			{
				$affected = $db->executeAffected(
					"UPDATE {$this->jobsTable}
					SET status = 'failed', processed_at = ?
					WHERE id = ? AND status = 'processing' AND reserved_at = ?",
					[$now, $job->id, $job->reserved_at]
				);

				if ($affected !== 1)
				{
					return false;
				}

				return $this->insertFailedRecord($db, $job, $error);
			});

			$released += $failed === true ? 1 : 0;
		}

		return $released;
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
		return $this->transactional(function (Mysqli $db) use ($jobId, $error): bool
		{
			// Get the job data
			$sql = "SELECT * FROM {$this->jobsTable} WHERE id = ?";
			$jobs = $db->fetch($sql, [$jobId]);

			if (!$jobs || empty($jobs))
            {
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
				return false;
			}

			return $this->insertFailedRecord($db, $job, $error);
		}) === true;
	}

	/**
	 * Copies a job row into the failed jobs table.
	 *
	 * @param Mysqli $db Connection
	 * @param object $job Job row
	 * @param string $error Error message
	 * @return bool
	 */
	protected function insertFailedRecord(Mysqli $db, object $job, string $error): bool
	{
		$failedJobData = (object) [
			'id' => uniqid('failed_', true),
			'job_id' => $job->id,
			'queue' => $job->queue,
			'job_class' => $job->job_class,
			'job_name' => $job->job_name,
			'data' => $job->data,
			'attempts' => $job->attempts,
			'error' => $error,
			'failed_at' => date('Y-m-d H:i:s'),
		];

		return $db->insert($this->failedJobsTable, $failedJobData);
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
		return $this->transactional(function (Mysqli $db) use ($jobId): bool
		{
			// Get the failed job
			$sql = "SELECT * FROM {$this->failedJobsTable} WHERE job_id = ?";
			$failedJobs = $db->fetch($sql, [$jobId]);

			if (!$failedJobs || empty($failedJobs))
            {
				return false;
			}

			$failedJob = $failedJobs[0];
			[$maxRetries, $timeout] = $this->resolveRetrySettings($db, $failedJob);

			// Create new job entry
			$newJobData = (object) [
				'id' => uniqid('job_', true),
				'queue' => $failedJob->queue,
				'job_class' => $failedJob->job_class,
				'job_name' => $failedJob->job_name,
				'data' => $failedJob->data,
				'attempts' => 0,
				'max_retries' => $maxRetries,
				'timeout' => $timeout,
				'status' => 'pending',
				'created_at' => date('Y-m-d H:i:s'),
				'available_at' => date('Y-m-d H:i:s'),
				'reserved_at' => null,
				'processed_at' => null,
			];

			if (!$db->insert($this->jobsTable, $newJobData))
            {
				return false;
			}

			// Remove from failed jobs
			return $db->delete($this->failedJobsTable, $jobId, 'job_id');
		}) === true;
	}

	/**
	 * Works out the retry budget and timeout for a re-queued failed job.
	 *
	 * The failed_jobs table does not store them, so they come from the
	 * original jobs row (kept with status `failed`). If cleanup already
	 * removed it, the job class's own defaults are used, then 3 / 300.
	 *
	 * @param Mysqli $db Connection
	 * @param object $failedJob Failed job row
	 * @return array{0: int, 1: int} [max_retries, timeout]
	 */
	protected function resolveRetrySettings(Mysqli $db, object $failedJob): array
	{
		$original = $db->fetch(
			"SELECT max_retries, timeout FROM {$this->jobsTable} WHERE id = ?",
			[$failedJob->job_id ?? null]
		);

		if ($original)
		{
			return [(int) $original[0]->max_retries, (int) $original[0]->timeout];
		}

		$class = (string) $failedJob->job_class;
		if (class_exists($class) && is_subclass_of($class, \Proto\Jobs\JobInterface::class))
		{
			try
			{
				$job = new $class();
				return [$job->getMaxRetries(), $job->getTimeout()];
			}
			catch (\Throwable $e)
			{
				// Fall through to the defaults.
			}
		}

		return [3, 300];
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
