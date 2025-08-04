<?php declare(strict_types=1);
namespace Proto\Http\Loop;

use SplObjectStorage;

/**
 * This will prevent the script from timing out.
 */
set_time_limit(0);

/**
 * EventLoop
 *
 * Handles event loop execution.
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
	 * Constructs the EventLoop instance.
	 *
	 * @param int $tickInterval The tick interval in milliseconds.
	 * @param SplObjectStorage $events The events storage.
	 * @return void
	 */
	public function __construct(
		int $tickInterval = 10,
		protected SplObjectStorage $events = new SplObjectStorage()
	)
	{
		$this->timer = new TickTimer($tickInterval);
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
	 * Executes the event loop.
	 *
	 * @return void
	 */
	public function loop(): void
	{
		while ($this->isActive())
		{
			if (connection_aborted())
			{
				$this->end();
				return;
			}

			$this->tick();
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