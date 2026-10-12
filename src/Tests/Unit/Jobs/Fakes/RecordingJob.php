<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs\Fakes;

use Proto\Jobs\Job;

/**
 * RecordingJob
 *
 * Test job that records every payload it handles. A payload of
 * `['throw' => 'message']` makes it fail.
 *
 * @package Proto\Tests\Unit\Jobs\Fakes
 */
class RecordingJob extends Job
{
	/**
	 * Zero disables set_time_limit() so tests never cap the PHPUnit run.
	 *
	 * @var int $timeout
	 */
	protected int $timeout = 0;

	/**
	 * @var int $maxRetries
	 */
	protected int $maxRetries = 1;

	/**
	 * @var array $handled Payloads handled across all instances.
	 */
	public static array $handled = [];

	/**
	 * @var int $failedCalls Number of failed() hook invocations.
	 */
	public static int $failedCalls = 0;

	/**
	 * Execute the job.
	 *
	 * @param mixed $data Job data
	 * @return mixed
	 */
	public function handle(mixed $data): mixed
	{
		if (is_array($data) && isset($data['throw']))
		{
			throw new \RuntimeException((string) $data['throw']);
		}

		static::$handled[] = $data;
		return true;
	}

	/**
	 * Records the failure hook call.
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void
	{
		static::$failedCalls++;
	}

	/**
	 * Clears recorded state.
	 *
	 * @return void
	 */
	public static function resetRecords(): void
	{
		static::$handled = [];
		static::$failedCalls = 0;
	}
}
