<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * One second in microseconds.
 */
const MICROSECONDS_PER_SECOND = 1000000;

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
	 * @var int
	 */
	protected int $tickInterval;

	/**
	 * The last run time in seconds (with microsecond precision).
	 *
	 * @var float
	 */
	protected float $lastRunTime;

	/**
	 * Constructs a TickTimer instance.
	 *
	 * @param int $tickInSeconds The tick interval in seconds.
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
		return $seconds * MICROSECONDS_PER_SECOND;
	}

	/**
	 * Gets a high-precision Unix timestamp.
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
		return (int)($this->tickInterval / MICROSECONDS_PER_SECOND);
	}

	/**
	 * Gets the next scheduled run time.
	 *
	 * @return float
	 */
	public function getNextRunTime(): float
	{
		return $this->lastRunTime + ($this->tickInterval / MICROSECONDS_PER_SECOND);
	}

	/**
	 * Waits until the next tick cycle.
	 *
	 * @return void
	 */
	public function tick(): void
	{
		$nextRunTime = $this->getNextRunTime();
		$this->sleepUntil($nextRunTime);
		$this->lastRunTime = self::getTimestamp();
	}

	/**
	 * Sleeps until the given time.
	 *
	 * @param float $time Target sleep time.
	 * @return void
	 */
	protected function sleepUntil(float $time): void
	{
		$sleepDuration = $time - self::getTimestamp();
		if ($sleepDuration <= 0)
		{
			return;
		}

		usleep((int)($sleepDuration * MICROSECONDS_PER_SECOND));
	}
}