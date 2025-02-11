<?php declare(strict_types=1);
namespace Proto\Automation;

/**
 * Server
 *
 * This abstract class provides methods to set up server settings such as memory limit and time limit.
 *
 * @package Proto\Automation
 */
abstract class Server
{
	/**
	 * Sets up the server settings based on the provided ServerSettings object.
	 *
	 * @param ServerSettings $settings The server settings to apply.
	 * @return void
	 */
	public static function setup(ServerSettings $settings): void
	{
		if ($settings->setLimits === false)
		{
			return;
		}

		static::setMemoryLimit($settings->memoryLimit);
		static::setTimeLimit($settings->timeLimit);
	}

	/**
	 * Sets the memory limit for the server.
	 *
	 * @param string $memoryLimit The memory limit to set (e.g., '256M').
	 * @return void
	 */
	protected static function setMemoryLimit(string $memoryLimit): void
	{
		ini_set('memory_limit', $memoryLimit);
	}

	/**
	 * Sets the maximum execution time for the server.
	 *
	 * @param int $timeLimit The time limit in seconds.
	 * @return void
	 */
	protected static function setTimeLimit(int $timeLimit): void
	{
		set_time_limit($timeLimit);
	}
}