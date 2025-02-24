<?php declare(strict_types=1);
namespace Modules\Example;

use Proto\Module\Module;

/**
 * ExampleModule
 *
 * This module is an example of how to create a module in the Proto framework.
 *
 * @package Modules\Example
 */
class ExampleModule extends Module
{
    /**
     * This will activate the module.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->setConfigs();
    }

    /**
     * This will set the configs for the module.
     *
     * @return void
     */
    private function setConfigs(): void
    {
        setEnv('settingName', 'value');
    }

    /**
     * This will add events to the module.
     *
     * @return void
     */
    protected function addEvents(): void
    {
        /**
         * This will add an event for when a ticket is added.
         */
        $this->event('Ticket:add', fn($ticket): void => var_dump($ticket));
    }
}