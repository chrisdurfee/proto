<?php declare(strict_types=1);
namespace Proto\Jobs\Models;

use Proto\Models\Model;

/**
 * FailedJobModel
 *
 * Model for failed_jobs table.
 *
 * @package Proto\Jobs\Models
 */
class FailedJobModel extends Model
{
	/**
	 * @var string $tableName Table name
	 */
	protected static ?string $tableName = 'failed_jobs';

	/**
	 * @var string $alias Table alias
	 */
	protected static ?string $alias = 'failed_job';

	/**
	 * @var array $fields Model fields
	 */
	protected static array $fields = [
		'id',
		'jobId',
		'queue',
		'jobClass',
		'jobName',
		'data',
		'attempts',
		'error',
		'failedAt',
	];

	/**
	 * Get recent failed jobs.
	 *
	 * @param int $limit Number of jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public static function getRecent(int $limit = 50, int $offset = 0): array
	{
		$modifiers = [
			'orderBy' => (object) ['failedAt' => 'DESC'],
		];

		$result = static::getRows(null, $offset, $limit, $modifiers);
		return $result->rows ?? [];
	}

	/**
	 * Get failed jobs by queue.
	 *
	 * @param string $queue Queue name
	 * @param int $limit Number of jobs to retrieve
	 * @return array
	 */
	public static function getByQueue(string $queue, int $limit = 50): array
	{
		$filter = ['queue' => $queue];

		$modifiers = [
			'orderBy' => (object) ['failedAt' => 'DESC'],
		];

		$result = static::getRows($filter, 0, $limit, $modifiers);
		return $result->rows ?? [];
	}

	/**
	 * Clean up old failed jobs.
	 *
	 * @param int $olderThanDays Delete failed jobs older than this many days
	 * @return int Number of jobs deleted
	 */
	public static function cleanup(int $olderThanDays = 30): int
	{
		$cutoffDate = date('Y-m-d H:i:s', time() - ($olderThanDays * 24 * 60 * 60));

		$filter = [
            ['failedAt', '<', $cutoffDate]
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
	 * Get failed job statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public static function getStatistics(?string $queue = null): array
	{
		$filter = $queue ? [['queue' => $queue]] : null;
		$totalResult = static::count($filter);
		$total = $totalResult->count ?? 0;

		// Get recent failures (last 24 hours)
		$yesterday = date('Y-m-d H:i:s', time() - 86400);
        $filter[] = ['failedAt', '>', $yesterday];
		$recentResult = static::count($filter);
		$recent = $recentResult->count ?? 0;

		return [
			'total' => (int) $total,
			'recent' => (int) $recent,
		];
	}
}
