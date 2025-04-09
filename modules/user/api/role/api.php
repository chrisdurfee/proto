<?php declare(strict_types=1);
namespace Modules\User\Api\Role;

use Modules\User\Controllers\RoleController;
use Modules\User\Controllers\UserRoleController;

/**
 * User Role API Routes
 *
 * This will handle the API routes for the User Roles.
 */
router()
	->resource('user/:userId/role', UserRoleController::class);

/**
 * Role API Routes
 *
 * This will handle the API routes for the Roles.
 */
router()
	->resource('user/role', RoleController::class);