<?php declare(strict_types=1);
namespace App\Automation;

use App\Auth;
use Proto\Base;
use Proto\Http\Response;

/**
 * Process
 *
 * This will be the base process class.
 *
 * @package App\Automation
 */
abstract class Process extends Base
{
    /**
     * @var object $service
     */
    protected $service;

    /**
     * @var Benchmark $benchmark
     */
    protected $benchmark;

    /**
     * @var string $date
     */
    public $date;

    /**
     *
     * @param string|null $date
     */
    public function __construct(?string $date = null)
    {
        parent::__construct();

        if (self::checkOrigination() !== true)
        {
            die;
        }

        /**
         * This will set the database caching to true to
         * help with performance.
         */
        setEnv('dbCaching', true);

        $this->setupDate($date);
        $this->setupBenchmark();
        $this->setupLimits();
    }

    /* this can set limits on the service */
    protected $setLimits = true;
    protected $memoryLimit = '2800M';
    protected $timeLimit = 3400;

    /**
     * This will setup the service limits.
     *
     * @return void
     */
    protected function setupLimits(): void
    {
        $settings = new ServerSettings(
            $this->setLimits,
            $this->memoryLimit,
            $this->timeLimit
        );

        Server::setup($settings);
    }

    /**
     * This will get the date.
     *
     * @return string|null
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * This will se tthe service date.
     *
     * @param string|null $date
     * @return void
     */
    public function setupDate(?string $date = null)
    {
        $this->date = $date ?? date('Y-m-d');
    }

    /**
     * This will setup the service benchmark.
     *
     * @return void
     */
    protected function setupBenchmark(): void
    {
        $this->benchmark = new Benchmark();
    }

    /**
     * This will get a routine by class name.
     *
     * @param string $routine
     * @return object|bool
     */
    public static function getRoutine(string $routine)
    {
        if (!isset($routine))
        {
            return false;
        }

        $routine = str_replace('.', '', $routine);

        /**
         * @var object $class
         */
        $class = __NAMESPACE__ . '\\Processes\\' . $routine . 'Routine';
        return new $class();
    }

    /**
     * This will check the environment origin making the request.
     *
     * @return bool|object
     */
    protected static function checkOrigination()
    {
        global $argv;
        if ((!isset($argv)) && !self::isLoggedIn())
        {
            return new Response([
                'success' => false,
                'error' => 'no permission to run the service'
            ], 403);
        }
        return true;
    }

    /**
     * This will check if the user is logged in.
     *
     * @return bool
     */
    protected static function isLoggedIn(): bool
    {
		return Auth::user()->isLoggedIn();
    }

    /**
     * This will stop the process if it's still running.
     */
    public function __destruct()
    {
        die();
    }
}