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
     *
     * @return void
     */
    public function __construct()
    {
        $this->run();
    }

    /**
     * The logic to be executed when the event is created.
     *
     * @return void
     */
    protected function run(): void
    {
        // Implement event-specific logic in subclasses.
    }

    /**
     * Encodes the given data as JSON and sends it as a message to the client.
     *
     * @param mixed $data The data to be sent as a message.
     * @return void
     */
    public function message(mixed $data): void
    {
        new Message($data);
        $this->flush();
    }

    /**
     * Flushes the output buffer, sending the data to the client.
     *
     * @return self
     */
    public function flush(): self
    {
        flush();
        if ( ob_get_level() > 0)
        {
            @ob_flush();
        }

        return $this;
    }
}