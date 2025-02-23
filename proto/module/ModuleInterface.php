<?php declare(strict_types=1);
namespace Proto\Module;

/**
 * Module
 *
 * This will create a module that can be used by the application.
 *
 * Modules are set up during bootstrapping and can
 * register events and other functions to execute after the
 * application is booted.
 *
 * @package Proto\Module
 * @abstract
 */
Interface ModuleInterface
{
	/**
	 * This will add an event.
	 *
	 * @param string $key The event key.
	 * @param callable $callBack The callback function to execute.
	 * @return string The event identifier.
	 */
	protected function event(string $key, callable $callBack): string;

	/**
	 * This will add moduile events.
	 *
	 * @return void
	 */
	protected function addEvents(): void;

	/**
	 * This will initialize the module.
	 *
	 * @return void
	 */
	public function init(): void;

    /**
     * This will add the module services.
     *
     * @return void
     */
    protected function addServices(): void;
}