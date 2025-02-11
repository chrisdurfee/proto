<?php declare(strict_types=1);
namespace Proto\Automation;

/**
 * Server
 *
 * This will set up the server settings.
 *
 * @package Proto\Automation
 */
abstract class Server
{
	/**
	 * This will set up the server settings.
	 *
	 * @param ServerSettings $settings
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
	 * This will set the memory limit.
	 *
	 * @param string $memoryLimit
	 * @return void
	 */
	protected static function setMemoryLimit(string $memoryLimit): void
	{
		ini_set('memory_limit', $memoryLimit);
	}

	/**
	 * This will set the time limit.
	 *
	 * @param int $timeLimit
	 * @return void
	 */
	protected static function setTimeLimit(int $timeLimit): void
	{
		set_time_limit($timeLimit);
	}
}