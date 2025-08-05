<?php declare(strict_types=1);
namespace Proto\Http\Loop;

/**
 * EventInterface
 *
 * Defines the contract for an event that runs within an event loop.
 *
 * @package Proto\Http\Loop
 */
interface EventInterface
{
	/**
	 * Executes on each tick of the event loop.
	 *
	 * Implementations should define what happens during each cycle.
	 *
	 * @return void
	 */
	public function tick(): void;
}