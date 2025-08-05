<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * AsyncEventInterface
 *
 * Defines the contract for an asynchronous event that runs within an event loop.
 *
 * @package Proto\Http\Loop
 */
interface AsyncEventInterface extends EventInterface
{
    /**
     * This will check if the event is terminated.
     *
     * @return bool
     */
    public function isTerminated(): bool;
}
