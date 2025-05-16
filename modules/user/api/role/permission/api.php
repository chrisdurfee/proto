<?php declare(strict_types=1);
namespace Modules\User\Api\Role;

use Modules\User\Controllers\RolePermissionController;

/**
 * Role Permission API Routes
 *
 * This file contains the API routes for the permission module.
 */
router()
	->resource('user/role/:roleId/permission', RolePermissionController::class);