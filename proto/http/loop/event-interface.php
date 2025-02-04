<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * EventInterface
 *
 * This will define the event interface.
 *
 * @package Proto\Http\Loop
 */
interface EventInterface
{
    /**
     * This will be called on each tick of the
     * event loop.
     *
     * @return void
     */
    public function tick() : void;
}
