<?php declare(strict_types=1);
namespace App\Automation;

/**
 * Server
 *
 * This will set up a server settings object.
 *
 * @package App\Automation
 */
class ServerSettings
{
    /**
     * This will set up the settings.
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