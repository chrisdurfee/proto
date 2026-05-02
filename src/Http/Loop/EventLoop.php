<?php declare(strict_types=1);
namespace Proto\Http\Loop;

use SplObjectStorage;

/**
 * EventLoop
 *
 * Handles event loop execution.
 *
 * The loop terminates when ANY of the following happens:
 *   - `end()` is called externally,
 *   - the client connection is reported as aborted,
 *   - `maxDurationSeconds` (when > 0) elapses since the loop started.
 *
 * The third bound is critical for SSE streams behind nginx/Vite proxies,
 * where `connection_aborted()` is unreliable. Without a deadline a stuck
 * worker can never recycle, leading to PHP-FPM saturation.
 *
 * @package Proto\Http\Loop
 */
class EventLoop
{
	/**
	 * The tick timer instance.
	 *
	 * @var TickTimer
	 */
	protected TickTimer $timer;

	/**
	 * Indicates if the loop is active.
	 *
	 * @var bool
	 */
	protected bool $active = true;

	/**
	 * Maximum loop duration in seconds. Zero disables the deadline (use
	 * with caution — only when callers enforce their own timeout).
	 *
	 * @var int
	 */
	protected int $maxDurationSeconds;

	/**
	 * Wall-clock time when the loop started.
	 *
	 * @var float|null
	 */
	protected ?float $startedAt = null;

	/**
	 * Constructs the EventLoop instance.
	 *
	 * @param int $tickInterval The tick interval in seconds.
	 * @param SplObjectStorage $events The events storage.
	 * @param int $maxDurationSeconds Hard upper bound on loop runtime.
	 *   Defaults to 300 (5 minutes); set to 0 to disable.
	 * @return void
	 */
	public function __construct(
		int $tickInterval = 10,
		protected SplObjectStorage $events = new SplObjectStorage(),
		int $maxDurationSeconds = 300
	)
	{
		$this->timer = new TickTimer($tickInterval);
		$this->maxDurationSeconds = max(0, $maxDurationSeconds);
	}

	/**
	 * Updates the maximum loop duration. Pass 0 to disable the deadline.
	 *
	 * @param int $seconds
	 * @return void
	 */
	public function setMaxDuration(int $seconds): void
	{
		$this->maxDurationSeconds = max(0, $seconds);
	}

	/**
	 * Checks if the loop is active.
	 *
	 * @return bool
	 */
	protected function isActive(): bool
	{
		return $this->active;
	}

	/**
	 * Returns true once the loop has exceeded its configured deadline.
	 *
	 * @return bool
	 */
	protected function isOverDeadline(): bool
	{
		if ($this->maxDurationSeconds <= 0 || $this->startedAt === null)
		{
			return false;
		}

		return (microtime(true) - $this->startedAt) >= $this->maxDurationSeconds;
	}

	/**
	 * Executes the event loop.
	 *
	 * @return void
	 */
	public function loop(): void
	{
		$this->startedAt = microtime(true);

		while ($this->isActive())
		{
			if (connection_aborted())
			{
				$this->end();
				return;
			}

			if ($this->isOverDeadline())
			{
				$this->end();
				return;
			}

			$this->tick();

			if (!$this->isActive())
			{
				break;
			}

			$this->timer->tick();
		}
	}

	/**
	 * Adds an event to the loop.
	 *
	 * @param EventInterface $event The event instance.
	 * @return void
	 */
	public function addEvent(EventInterface $event): void
	{
		$this->events->attach($event);
	}

	/**
	 * Removes an event from the loop.
	 *
	 * @param EventInterface $event The event instance.
	 * @return void
	 */
	public function removeEvent(EventInterface $event): void
	{
		$this->events->detach($event);
	}

	/**
	 * Executes the tick method on each event.
	 *
	 * @return void
	 */
	protected function tick(): void
	{
		if (count($this->events) < 1)
		{
			return;
		}

		foreach ($this->events as $event)
		{
			$event->tick();

			if ($event instanceof AsyncEventInterface && $event->isTerminated())
			{
				$this->removeEvent($event);
			}
		}
	}

	/**
	 * Stops the event loop.
	 *
	 * @return void
	 */
	public function end(): void
	{
		$this->active = false;
	}
}
