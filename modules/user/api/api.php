<?php declare(strict_types=1);
namespace Modules\User\Api;

use Modules\User\Controllers\UserController;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
	->patch('user/:id/status', [UserController::class, 'updateStatus'])
	->patch('user/:id/verify-email', [UserController::class, 'verifyEmail'])
	->patch('user/:id/unsubscribe', [UserController::class, 'unsubscribe'])
	->patch('user/:id/update-credentials', [UserController::class, 'updateCredentials'])
	->resource('user', UserController::class);