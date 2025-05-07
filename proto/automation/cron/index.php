<?php declare(strict_types=1);
include_once __DIR__ . '/../../../proto/autoload.php';

use Proto\Automation\Cron\Cron;

/**
 * This will get the routine name from the command line args
 * and run the routine.
 *
 * This should be the namespace of the routine.
 *
 * e.g. Proto\Automation\Routines\ExampleRoutine
 *
 * This will run the routine: Proto\Automation\Routines\ExampleRoutine
 *
 * @var string|null $routine
 */
$routine = $argv[1] ?? null;
Cron::run($routine);