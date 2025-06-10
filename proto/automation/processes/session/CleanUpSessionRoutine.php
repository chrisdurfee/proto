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
	 * Performs the routine process.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		// Remove expired sessions
		$expiredSessions = UserSession::getExpiredSessions();
		foreach ($expiredSessions as $sessionId)
		{
			UserSession::remove((object) [
				'id' => $sessionId,
			]);
		}

		// Remove empty sessions
		$emptySessions = UserSession::getEmptySessions();
		foreach ($emptySessions as $sessionId)
		{
			UserSession::remove((object) [
				'id' => $sessionId,
			]);
		}
	}
}