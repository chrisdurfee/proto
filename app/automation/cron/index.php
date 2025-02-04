<?php declare(strict_types=1);
include_once __DIR__ . '/../../../proto/autoload.php';

use App\Automation\Cron\Cron;

/**
 * This will get the routine name from the command line args
 * and run the routine.
 *
 * This should be the namespace of the routine wihout the
 * "Routine" suffix.
 *
 * e.g. /Example
 *
 * This will run the routine: App\Automation\Routines\ExampleRoutine
 *
 * @var string|null $routine
 */
$routine = $argv[1] ?? null;
Cron::run($routine);