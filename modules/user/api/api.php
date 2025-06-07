<?php declare(strict_types=1);
namespace Modules\User\Api;

use Modules\User\Controllers\UserController;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
	->patch('user/status', [UserController::class, 'updateStatus'])
	->patch('user/verify-email', [UserController::class, 'verifyEmail'])
	->resource('user', UserController::class);