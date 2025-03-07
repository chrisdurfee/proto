<?php declare(strict_types=1);
namespace Common\Automation\Cron;

use Proto\Http\Response;
use Proto\Automation\Process;
use Proto\Utils\Strings;

/**
 * Class Cron
 *
 * This will handle cron jobs.
 *
 * @package Common\Automation\Cron
 */
class Cron
{
    /**
     * This will get the routine name.
     *
     * @param string|null $routine
     * @return string|null
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
        return join('\\', $parts);
    }

    /**
     * This will run a routine.
     *
     * @param string|null $routine
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

        $routine = Process::getRoutine($routineName);
        if (empty($routine))
        {
            self::error('The routine not found.');
            return;
        }

        $routine->run();
    }

    /**
     * This will display an error message.
     *
     * @param string $message
     * @param int $code
     * @return void
     */
    protected static function error(string $message, int $code = 500): void
    {
        new Response([
            'message' => $message
        ], $code);
        die;
    }
}