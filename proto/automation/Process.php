<?php declare(strict_types=1);
namespace Proto\Automation;

use Proto\Base;
use Proto\Http\Response;

/**
 * Class Process
 *
 * This class serves as the base process class for automation tasks.
 *
 * @package Proto\Automation
 */
abstract class Process extends Base
{
	/**
	 * @var object $service The service object used in the process.
	 */
	protected object $service;

	/**
	 * @var Benchmark $benchmark The benchmark object for performance measurement.
	 */
	protected Benchmark $benchmark;

	/**
	 * @var string $date The date for the process.
	 */
	public string $date;

	/**
	 * @var bool $setLimits Flag to determine if limits should be set.
	 */
	protected bool $setLimits = true;

	/**
	 * @var string $memoryLimit The memory limit for the process.
	 */
	protected string $memoryLimit = '2800M';

	/**
	 * @var int $timeLimit The time limit for the process.
	 */
	protected int $timeLimit = 3400;

	/**
	 * Constructor to set up the process.
	 *
	 * @param string|null $date The date for the process.
	 */
	public function __construct(?string $date = null)
	{
		parent::__construct();

		if (self::checkOrigination() !== true)
		{
			die;
		}

		// Enable database caching for performance improvement.
		setEnv('dbCaching', true);

		$this->setupDate($date);
		$this->setupBenchmark();
		$this->setupLimits();
	}

	/**
	 * Sets up the service limits.
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
	 * Gets the date for the process.
	 *
	 * @return string|null The date for the process.
	 */
	public function getDate(): ?string
	{
		return $this->date;
	}

	/**
	 * Sets the date for the process.
	 *
	 * @param string|null $date The date to set.
	 * @return void
	 */
	public function setupDate(?string $date = null): void
	{
		$this->date = $date ?? date('Y-m-d');
	}

	/**
	 * Sets up the benchmark for the process.
	 *
	 * @return void
	 */
	protected function setupBenchmark(): void
	{
		$this->benchmark = new Benchmark();
	}

	/**
	 * Gets a routine by class name.
	 *
	 * @param string $routine The name of the routine class.
	 * @return object|bool The routine object or false if not found.
	 */
	public static function getRoutine(string $routine): object|bool
	{
		if (!isset($routine))
		{
			return false;
		}

		$routine = str_replace('.', '', $routine);

		/**
		 * @var object $class The fully qualified class name of the routine.
		 */
		$class = __NAMESPACE__ . '\\Processes\\' . $routine . 'Routine';
		return new $class();
	}

	/**
	 * Checks the environment origin making the request.
	 *
	 * @return bool|object True if the origin is valid, otherwise a Response object with an error.
	 */
	protected static function checkOrigination(): bool|object
	{
		global $argv;
		if ((!isset($argv)))
		{
			return new Response([
				'success' => false,
				'error' => 'no permission to run the service'
			], 403);
		}
		return true;
	}

	/**
	 * Destructor to stop the process if it's still running.
	 */
	public function __destruct()
	{
		die();
	}
}