<?php declare(strict_types=1);
namespace Proto\Automation\Processes;

use Proto\Automation\Process;

/**
 * Class Routine
 *
 * This class serves as the base routine class for automation tasks.
 *
 * @package Proto\Automation\Processes
 */
abstract class Routine extends Process
{
	/**
	 * Runs the routine.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->benchmark->start();
		$this->process();
		$this->benchmark->stop();
	}

	/**
	 * Performs the routine process. Should be overridden by subclasses.
	 *
	 * @return void
	 */
	abstract protected function process(): void;
}