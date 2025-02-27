<?php declare(strict_types=1);
namespace Modules\User\User\Api\Account;

use Modules\User\Controllers\UserController;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
    ->resource('user/:userId/account', UserController::class);