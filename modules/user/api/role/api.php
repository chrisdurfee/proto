<?php declare(strict_types=1);
namespace Modules\User\Api\Role;

use Modules\User\Controllers\RoleController;

/**
 * Role API Routes
 *
 * This file contains the API routes for the Role module.
 */
router()
    ->resource('user/:userId/role', RoleController::class);