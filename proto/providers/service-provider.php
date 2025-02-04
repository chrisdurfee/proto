<?php declare(strict_types=1);
namespace Proto\Providers;

use Proto\Events\Events;

/**
 * ServiceProvider
 *
 * This will create a service provider that can be used by the application.
 *
 * Serice providers are set up during bootstraping and can
 * register events and other functions to do after the
 * application is booted.
 *
 * @package Proto\Providers *
 * @abstract
 */
abstract class ServiceProvider
{
    /**
     * This will add an event.
     *
     * @param string $key
     * @param callable $callBack
     * @return string
     */
    protected function event(string $key, $callBack): string
    {
        return Events::on($key, $callBack);
    }

    /**
     * This will add service events.
     *
     * @return void
     */
    protected function addEvents()
    {

    }

    /**
     * This will init the service.
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
    abstract public function activate();

    /**
     * This will be called when the service is deactivated.
     *
     * @return void
     */
    public function deactivate()
    {

    }
}