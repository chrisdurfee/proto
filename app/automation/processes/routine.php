<?php declare(strict_types=1);
namespace App\Automation\Processes;

use App\Automation\Process;

/**
 * Routine
 *
 * This will be the base routine class.
 *
 * @package App\Automation\Processes
 */
abstract class Routine extends Process
{
    /**
     * This will run the routine.
     *
     * @return void
     */
    public function run()
    {
        $this->benchmark->start();
        $this->process();
        $this->benchmark->stop();
    }

    /**
     * This should be overridden to perform the routine process.
     *
     * @return void
     */
    abstract protected function process();
}