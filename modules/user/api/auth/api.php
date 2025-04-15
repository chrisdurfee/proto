<?php declare(strict_types=1);
namespace Modules\User\Api\Account;

use Modules\User\Controllers\AuthController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;

/**
 * User Auth API Routes
 */
router()
    ->resource('user/auth', AuthController::class)
    ->post('login', [AuthController::class, 'login'])
    ->post('register', [AuthController::class, 'register'])
    ->post('user/auth/get-csrf-token', [AuthController::class, 'getToken'], [
        CrossSiteProtectionMiddleware::class
    ]);