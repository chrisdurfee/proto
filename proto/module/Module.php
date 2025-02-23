<?php declare(strict_types=1);
namespace Proto\Module;

use Proto\Events\Events;

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
abstract class Module implements ModuleInterface
{
	/**
	 * This will add an event.
	 *
	 * @param string $key The event key.
	 * @param callable $callBack The callback function to execute.
	 * @return string The event identifier.
	 */
	protected function event(string $key, callable $callBack): string
	{
		return Events::on($key, $callBack);
	}

	/**
	 * This will add service events.
	 *
	 * @return void
	 */
	protected function addEvents(): void
	{
	}

	/**
	 * This will initialize the service.
	 *
	 * @return void
	 */
	public function init(): void
	{
		$this->addEvents();
		$this->activate();
	}

	/**
	 * This will be called when the service is activated.
	 *
	 * @return void
	 */
	abstract public function activate(): void;

	/**
	 * This will be called when the service is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void
	{
	}
}