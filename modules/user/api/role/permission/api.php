<?php declare(strict_types=1);
namespace Modules\User\Api\Role\Permission;

use Modules\User\Controllers\PermissionRoleController;

/**
 * Role Permission API Routes
 *
 * This file contains the API routes for the permission module.
 */
router()
	->resource('user/role/:roleId/permission', PermissionRoleController::class);