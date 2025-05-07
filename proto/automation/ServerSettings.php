<?php declare(strict_types=1);
namespace Proto\Automation;

/**
 * Class ServerSettings
 *
 * This class sets up a server settings object.
 *
 * @package Proto\Automation
 */
class ServerSettings
{
	/**
	 * Constructor to set up the settings.
	 *
	 * @param bool $setLimits
	 * @param string $memoryLimit
	 * @param int $timeLimit
	 * @return void
	 */
	public function __construct(
		public bool $setLimits = true,
		public string $memoryLimit = '2800M',
		public int $timeLimit = 3400
	)
	{
	}
}