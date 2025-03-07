<?php declare(strict_types=1);
namespace Common\API;

use Common\Controllers\UserController;

/**
 * User Routes
 *
 * This file contains the API routes for the User module.
 */
router()
    ->resource('user', UserController::class);