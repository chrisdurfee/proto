<?php declare(strict_types=1);
namespace Common\Jobs;

/**
 * ExampleJob
 *
 * This is an example job.
 *
 * @package Common\Jobs
 */
class ExampleJob
{
	/**
	 * This will run the job.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function handle(mixed $data): mixed
	{
		// do something
		return false;
	}
}