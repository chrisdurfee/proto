<?php declare(strict_types=1);
namespace Proto\Jobs\Drivers;

/**
 * ReleasesStaleJobs
 *
 * Implemented by drivers that reserve jobs in shared storage and can
 * recover reservations left behind by a worker that died mid-job.
 *
 * @package Proto\Jobs\Drivers
 */
interface ReleasesStaleJobs
{
	/**
	 * Return jobs reserved longer than the timeout to the queue (or fail
	 * them when their retries are exhausted).
	 *
	 * @param int $timeout Seconds a reservation may last
	 * @return int Number of jobs released or failed
	 */
	public function releaseStaleJobs(int $timeout = 900): int;
}
