<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * Event
 *
 * Provides the base class for creating events in the event loop.
 *
 * @package Proto\Http\Loop
 * @abstract
 */
abstract class Event implements EventInterface
{
	/**
	 * Constructs an Event instance and runs the event.
	 */
	public function __construct()
	{
		$this->run();
	}

	/**
	 * Defines the logic to be executed when the event is created.
	 * Subclasses must implement this method.
	 */
	abstract protected function run(): void;

	/**
	 * Encodes the given data as JSON and sends it as a message to the client.
	 *
	 * @param mixed $data The data to be sent as a message.
	 */
	public function message(mixed $data): void
	{
		// Message class handles rendering and flushing
		new Message($data);
	}

	/**
	 * Flushes the output buffer, sending the data to the client.
	 * Uses aggressive flushing to ensure real-time delivery.
	 *
	 * @return self
	 */
	public function flush(): self
	{
		// Only flush if output buffering is active
		$levels = ob_get_level();
		if ($levels > 0)
		{
			// Flush each buffer level with safety limit
			$maxLevels = min($levels, 10); // Prevent infinite loops
			for ($i = 0; $i < $maxLevels; $i++)
			{
				@ob_flush();
			}
		}

		// System flush - always safe to call
		flush();

		return $this;
	}
}