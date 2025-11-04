<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * UpdateEvent
 *
 * Represents an update event in the event loop.
 *
 * @package Proto\Http\Loop
 */
class UpdateEvent extends Event
{
	/**
	 * The callback function to be executed on each tick.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * Constructs an UpdateEvent instance.
	 *
	 * @param callable $callback The function to execute on each tick.
	 */
	public function __construct(callable $callback)
	{
		parent::__construct();
		$this->callback = $callback;
	}

	/**
	 * Defines the logic to be executed when the event is created.
	 * Subclasses must implement this method.
	 */
	protected function run(): void
	{

	}

	/**
	 * Executes on each tick of the event loop.
	 *
	 * @return void
	 */
	public function tick(): void
	{
		// Call the callback and handle the result.
		$result = ($this->callback)($this);
		if ($result)
		{
			$this->message($result);
		}
	}
}