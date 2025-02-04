<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * One second in microseconds.
 */
const SECOND_IN_MICROSECONDS = 1000000;

/**
 * TickTimer
 *
 * Handles tick timer execution.
 *
 * @package Proto\Http\Loop
 */
class TickTimer
{
	/**
	 * The tick interval in microseconds.
	 *
	 * @var int $tickInterval
	 */
	protected int $tickInterval;

	/**
	 * The last run time as a float.
	 *
	 * @var float $lastRunTime
	 */
	protected float $lastRunTime;

	/**
	 * Constructs a TickTimer instance.
	 *
	 * @param int $tickInSeconds The tick interval in seconds.
	 * @return void
	 */
	public function __construct(int $tickInSeconds = 10)
	{
		$this->tickInterval = self::convertToMicroseconds($tickInSeconds);
		$this->lastRunTime = self::getTimestamp();
	}

	/**
	 * Converts seconds into microseconds.
	 *
	 * @param int $seconds The number of seconds.
	 * @return int
	 */
	protected static function convertToMicroseconds(int $seconds): int
	{
		return $seconds * SECOND_IN_MICROSECONDS;
	}

	/**
	 * Gets a Unix timestamp with microseconds precision.
	 *
	 * @return float
	 */
	public static function getTimestamp(): float
	{
		return microtime(true);
	}

	/**
	 * Gets the tick interval in seconds.
	 *
	 * @return int
	 */
	public function getTickInSeconds(): int
	{
		return (int)($this->tickInterval / SECOND_IN_MICROSECONDS);
	}

	/**
	 * Gets the next run time as a float.
	 *
	 * @return float
	 */
	public function getNextRunTime(): float
	{
		return $this->lastRunTime + ($this->tickInterval / SECOND_IN_MICROSECONDS);
	}

	/**
	 * Executes the tick and sleeps until the next run time.
	 *
	 * @return void
	 */
	public function tick(): void
	{
		$nextRunTime = $this->getNextRunTime();
		$this->sleep($nextRunTime);
		$this->lastRunTime = self::getTimestamp();
	}

	/**
	 * Sleeps until the next run time.
	 *
	 * @param float $time The target time to sleep until.
	 * @return void
	 */
	public function sleep(float $time): void
	{
		$sleep = $time - self::getTimestamp();
		if ($sleep > 0)
		{
			$timeInMicroseconds = (int)($sleep * SECOND_IN_MICROSECONDS);
			usleep($timeInMicroseconds);
		}
	}
}