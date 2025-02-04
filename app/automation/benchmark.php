<?php declare(strict_types=1);
namespace App\Automation;

/**
 * Benchmark
 *
 * This will be a benchmark class.
 *
 * @package App\Automation
 */
class Benchmark
{
	/**
	 * @var int $timerStart
	 */
	protected float $timerStart;

	/**
	 * @var int $timerEnd
	 */
	protected float $timerEnd;

	/**
	 * @var string $totalTime
	 */
	protected string $totalTime = '0.0';

	/**
	 * @var string $status
	 */
	protected string $status = 'init';

	/**
	 * This will update the status.
	 *
	 * @param string $status
	 * @return void
	 */
	protected function setStatus(string $status): void
	{
		$this->status = $status;
	}

	/**
	 * This will get the status.
	 *
	 * @return string
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 * This will start the timer.
	 *
	 * @return void
	 */
	public function start(): void
	{
		$this->timerStart = self::getTime();
		$this->setStatus('started');
	}

	/**
	 * This will stop the timer.
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
	 * This will get the total time.
	 *
	 * @return string
	 */
	public function getTotal(): string
	{
		return $this->totalTime;
	}

	/**
	 * This will get the time.
	 *
	 * @return float
	 */
	protected static function getTime(): float
	{
		return microtime(true);
	}

	/**
	 * This will get the total time.
	 *
	 * @param float $stop
	 * @param float $start
	 * @return string
	 */
	protected static function getTotalTime($stop = 0, $start = 0): string
	{
		$time = ($stop - $start);
		return sprintf('%f', $time);
	}
}