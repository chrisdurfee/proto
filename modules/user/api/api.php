<?php declare(strict_types=1);
namespace Modules\User\Api;

use Modules\User\Controllers\UserController;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
	->all('user/:id/status', [UserController::class, 'updateStatus'])
	->patch('user/:id/verify-email', [UserController::class, 'verifyEmail'])
	->patch('user/:id/update-credentials', [UserController::class, 'updateCredentials'])
	->resource('user', UserController::class);