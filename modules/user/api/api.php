<?php declare(strict_types=1);
namespace Modules\User\User\Api;

use Modules\User\Controllers\UserController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
    ->resource('user', UserController::class)
    ->middleware([
        CrossSiteProtectionMiddleware::class
    ]);