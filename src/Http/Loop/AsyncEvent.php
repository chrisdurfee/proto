<?php declare(strict_types=1);
namespace Proto\Http\Loop;

use Fiber;

/**
 * AsyncEvent
 *
 * This class handles events using PHP Fibers.
 *
 * @package Proto\Http\Loop
 */
class AsyncEvent extends Event implements AsyncEventInterface
{
	/**
	 * The Fiber instance.
	 *
	 * @var Fiber
	 */
	private Fiber $fiber;

	/**
	 * Constructor.
	 *
	 * @param callable $callback The callback to be executed.
	 * @return void
	 */
	public function __construct(callable $callback)
	{
		// we wrap the callback so it can suspend() and yield control back
		$this->fiber = new Fiber(fn() => $callback($this));
	}

	/**
	 * This is overridden from the parent class.
	 *
	 * @return void
	 */
	protected function run(): void
	{
	}

	/**
	 * This will check if the fiber is terminated.
	 *
	 * @return bool
	 */
	public function isTerminated(): bool
	{
		return $this->fiber->isTerminated();
	}

	/**
	 * This will be called by the EventLoop.
	 *
	 * @return void
	 */
	public function tick(): void
	{
		// if not yet started, start it
		if (! $this->fiber->isStarted())
		{
			$result = $this->fiber->start();
		}
		else if ($this->fiber->isSuspended())
		{
			$result = $this->fiber->resume();
		}
		else
		{
			// fiber is terminated, the loop will auto-remove us
			return;
		}

		if (! empty($result))
		{
			$this->message($result);
		}
	}
}
