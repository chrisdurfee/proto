<?php declare(strict_types=1);
namespace Proto\Automation\Cron;

use Proto\Http\Response;
use Proto\Automation\Process;
use Proto\Utils\Strings;

/**
 * Class Cron
 *
 * Handles cron jobs.
 *
 * @package Proto\Automation\Cron
 */
class Cron
{
	/**
	 * Retrieves the routine name in PascalCase with namespace separators.
	 *
	 * @param string|null $routine The routine identifier.
	 * @return string|null The formatted routine name, or null if input is empty.
	 */
	protected static function getRoutineName(?string $routine = null): ?string
	{
		if (empty($routine))
		{
			return null;
		}

		$parts = explode('/', $routine);
		foreach ($parts as $key => $value)
		{
			$parts[$key] = Strings::pascalCase($value);
		}
		return implode('\\', $parts);
	}

	/**
	 * Runs a given routine.
	 *
	 * @param string|null $routine The routine identifier.
	 * @return void
	 */
	public static function run(?string $routine): void
	{
		$routineName = self::getRoutineName($routine);
		if (empty($routineName))
		{
			self::error('No routine was setup.');
			return;
		}

		$routineInstance = Process::getRoutine($routineName);
		if (empty($routineInstance))
		{
			self::error('The routine not found.');
			return;
		}

		$routineInstance->run();
	}

	/**
	 * Displays an error message and halts execution.
	 *
	 * @param string $message The error message.
	 * @param int $code The HTTP error code.
	 * @return void
	 */
	protected static function error(string $message, int $code = 500): void
	{
		new Response(
			[
				'message' => $message
			],
			$code
		);
		die;
	}
}