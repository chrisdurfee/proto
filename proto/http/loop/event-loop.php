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
	 * @var TickTimer $timer
	 */
	protected TickTimer $timer;

	/**
	 * The collection of events.
	 *
	 * @var SplObjectStorage $events
	 */
	protected SplObjectStorage $events;

	/**
	 * Indicates if the loop is active.
	 *
	 * @var bool $active
	 */
	protected bool $active = true;

	/**
	 * Constructs the EventLoop instance.
	 *
	 * @param int $tickInterval The tick interval in milliseconds.
	 * @return void
	 */
	public function __construct(int $tickInterval = 10)
	{
		$this->timer = new TickTimer($tickInterval);
		$this->events = new SplObjectStorage();
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
		/**
		 * This will loop until the loop is stopped.
		 */
		while ($this->isActive())
		{
			// Stop the loop if the client aborts the connection (closes the page).
			if (connection_aborted())
			{
				$this->end();
				break;
			}

			$this->tick();

			/**
			 * This will check to stop if an event has
			 * stopped the loop.
			 */
			if (!$this->isActive())
			{
				break;
			}

			// Sleep the loop until the next run time.
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
