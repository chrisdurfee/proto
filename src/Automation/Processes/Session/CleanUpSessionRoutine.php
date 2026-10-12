<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Session;

use Proto\Automation\Processes\Routine;
use Proto\Http\Session\Models\UserSession;

/**
 * Class CleanUpSessionRoutine
 *
 * Removes expired sessions or empty sessions
 * from the session storage.
 *
 * @package Proto\Automation\Processes\Session
 */
class CleanUpSessionRoutine extends Routine
{
	/**
	 * Rows deleted per statement, so a large backlog never holds a long
	 * table lock.
	 *
	 * @var int
	 */
	protected const BATCH_SIZE = 1000;

	/**
	 * Upper bound on batches per run.
	 *
	 * @var int
	 */
	protected const MAX_BATCHES = 100;

	/**
	 * Performs the routine process.
	 *
	 * Deletes expired and empty sessions in bounded batches. (This used
	 * to call the instance finders statically, which fatals, and then
	 * removed one row per query.)
	 *
	 * @return void
	 */
	protected function process(): void
	{
		$sessions = new UserSession();

		$this->drain(fn(): int => $sessions->deleteExpiredSessions(static::BATCH_SIZE));
		$this->drain(fn(): int => $sessions->deleteEmptySessions(static::BATCH_SIZE));
	}

	/**
	 * Runs a batch delete until a batch comes back short.
	 *
	 * @param callable(): int $deleteBatch
	 * @return int Total rows deleted.
	 */
	protected function drain(callable $deleteBatch): int
	{
		$total = 0;
		for ($i = 0; $i < static::MAX_BATCHES; $i++)
		{
			$deleted = $deleteBatch();
			$total += $deleted;
			if ($deleted < static::BATCH_SIZE)
			{
				break;
			}
		}

		return $total;
	}
}
