<?php declare(strict_types=1);
namespace Modules\User\Api\Permission;

use Modules\User\Controllers\PermissionController;

/**
 * Permission Routes
 *
 * This will handle the API routes for the Permissions.
 */
router()
	->resource('user/permission', PermissionController::class);