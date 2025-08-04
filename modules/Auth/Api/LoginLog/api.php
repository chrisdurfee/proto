<?php declare(strict_types=1);
namespace Modules\Auth\Api\LoginLog;

use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Modules\Auth\Controllers\LoginLogController;

/**
 * Login Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class
	]))
	->get('auth/:userId/login-log', [LoginLogController::class, 'all']);
