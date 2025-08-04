<?php declare(strict_types=1);
namespace Modules\User\Api;

use Modules\User\Controllers\UserController;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()

	/**
	 * Status routes
	 */
	->patch('user/:id/status', [UserController::class, 'updateStatus'])

	/**
	 * Email routes
	 */
	->patch('user/:id/verify-email', [UserController::class, 'verifyEmail'])
	->all('user/unsubscribe', [UserController::class, 'unsubscribe'])

	/**
	 * Profile routes
	 */
	->patch('user/:id/update-credentials', [UserController::class, 'updateCredentials'])
	->resource('user', UserController::class);