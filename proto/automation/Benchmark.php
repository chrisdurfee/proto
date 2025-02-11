<?php declare(strict_types=1);
namespace Proto\Automation;

/**
 * Class Benchmark
 *
 * This class provides benchmarking functionality for measuring performance.
 *
 * @package Proto\Automation
 */
class Benchmark
{
	/**
	 * @var float $timerStart The start time of the benchmark.
	 */
	protected float $timerStart;

	/**
	 * @var float $timerEnd The end time of the benchmark.
	 */
	protected float $timerEnd;

	/**
	 * @var string $totalTime The total time measured by the benchmark.
	 */
	protected string $totalTime = '0.0';

	/**
	 * @var string $status The current status of the benchmark.
	 */
	protected string $status = 'init';

	/**
	 * Updates the status of the benchmark.
	 *
	 * @param string $status The new status.
	 * @return void
	 */
	protected function setStatus(string $status): void
	{
		$this->status = $status;
	}

	/**
	 * Gets the current status of the benchmark.
	 *
	 * @return string The current status.
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 * Starts the benchmark timer.
	 *
	 * @return void
	 */
	public function start(): void
	{
		$this->timerStart = self::getTime();
		$this->setStatus('started');
	}

	/**
	 * Stops the benchmark timer.
	 *
	 * @return void
	 */
	public function stop(): void
	{
		$this->timerEnd = self::getTime();
		$this->totalTime = self::getTotalTime($this->timerEnd, $this->timerStart);
		$this->setStatus('stopped');
	}

	/**
	 * Gets the total time measured by the benchmark.
	 *
	 * @return string The total time.
	 */
	public function getTotal(): string
	{
		return $this->totalTime;
	}

	/**
	 * Gets the current time in microseconds.
	 *
	 * @return float The current time.
	 */
	protected static function getTime(): float
	{
		return microtime(true);
	}

	/**
	 * Calculates the total time between the start and end times.
	 *
	 * @param float $stop The end time.
	 * @param float $start The start time.
	 * @return string The total time.
	 */
	protected static function getTotalTime(float $stop, float $start): string
	{
		$time = ($stop - $start);
		return sprintf('%f', $time);
	}
}