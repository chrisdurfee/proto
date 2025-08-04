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
	 * This will initialize the module.
	 *
	 * @return void
	 */
	public function init(): void;
}