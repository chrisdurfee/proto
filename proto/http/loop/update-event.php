<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * UpdateEvent
 *
 * This will create an update event.
 *
 * @package Proto\Http\Loop
 */
class UpdateEvent extends Event
{
    /**
     * @var callable $callback
     */
    private $callback;

    /**
     *
     * @param callable $callback
     * @return void
     */
    public function __construct(callable $callback)
    {
        parent::__construct();
        $this->callback = $callback;
    }

    /**
     * This will be called on each tick of the
     * event loop.
     *
     * @return void
     */
    public function tick(): void
    {
        /**
         * This will call the callback and check the result.
         */
        $callback = $this->callback;
        $result = $callback($this);
        if (!$result)
        {
            return;
        }

        $this->message($result);
    }
}
