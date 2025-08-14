<?php declare(strict_types=1);
namespace Proto\Jobs\Models;

use Proto\Models\Model;

/**
 * JobModel
 *
 * Model for jobs table.
 *
 * @package Proto\Jobs\Models
 */
class JobModel extends Model
{
	/**
	 * @var string $tableName Table name
	 */
	protected static ?string $tableName = 'jobs';

	/**
	 * @var string $alias Table alias
	 */
	protected static ?string $alias = 'job';

	/**
	 * @var array $fields Model fields
	 */
	protected static array $fields = [
		'id',
		'queue',
		'jobClass',
		'jobName',
		'data',
		'attempts',
		'maxRetries',
		'timeout',
		'status',
		'createdAt',
		'availableAt',
		'reservedAt',
		'processedAt',
	];

	/**
	 * Get jobs by status.
	 *
	 * @param string $status Job status
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getByStatus(string $status, int $limit = 50): array
	{
		return static::getRows(['status' => $status], 0, $limit) ?? [];
	}

	/**
	 * Get pending jobs for a queue.
	 *
	 * @param string $queue Queue name
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getPendingJobs(string $queue = 'default', int $limit = 50): array
	{
		$filter = [
			'queue' => $queue,
			'status' => 'pending'
		];

		$modifiers = [
			'orderBy' => (object) ['availableAt' => 'ASC'],
		];

		$result = static::getRows($filter, 0, $limit, $modifiers);
		return $result->rows ?? [];
	}

	/**
	 * Get processing jobs.
	 *
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getProcessingJobs(int $limit = 50): array
	{
		return static::getByStatus('processing', $limit);
	}

	/**
	 * Get completed jobs.
	 *
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getCompletedJobs(int $limit = 50): array
	{
		return static::getByStatus('completed', $limit);
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getFailedJobs(int $limit = 50): array
	{
		return static::getByStatus('failed', $limit);
	}

	/**
	 * Clean up old completed jobs.
	 *
	 * @param int $olderThanDays Delete jobs older than this many days
	 * @return int Number of jobs deleted
	 */
	public static function cleanupCompleted(int $olderThanDays = 7): int
	{
		$cutoffDate = date('Y-m-d H:i:s', time() - ($olderThanDays * 24 * 60 * 60));

		$filter = [
			['status', 'completed'],
            ['processedAt', '<', $cutoffDate]
		];

		$count = 0;
		$jobs = static::getRows($filter);
		if ($jobs && $jobs->rows)
        {
			foreach ($jobs->rows as $job)
            {
				if (static::remove($job))
                {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Get job statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public static function getStatistics(?string $queue = null): array
	{
		$stats = [
			'pending' => 0,
			'processing' => 0,
			'completed' => 0,
			'failed' => 0,
			'total' => 0,
		];

		$statuses = ['pending', 'processing', 'completed', 'failed'];

		foreach ($statuses as $status)
        {
			$filter = [
                ['status', $status]
            ];

			if ($queue !== null)
            {
				$filter[] = ['queue', $queue];
			}

			$result = static::count($filter);
			$count = $result->count ?? 0;
			$stats[$status] = (int) $count;
			$stats['total'] += (int) $count;
		}

		return $stats;
	}
}
