<?php declare(strict_types=1);
namespace Modules\User\Api\Account;

use Modules\User\Controllers\UserController;

/**
 * User Auth API Routes
 */
router()
    ->resource('user/auth', UserController::class);