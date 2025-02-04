<?php
namespace App\Controllers;

use Proto\Controllers\ModelController;
use Proto\Http\Session;

/**
 * Controller
 * @abstract
 */
abstract class Controller extends ModelController
{
    /**
     * This will get the session.
     *
     * @return object
     */
    protected function getSession(): object
    {
        return Session::getInstance();
    }
}