<?php declare(strict_types=1);
namespace Modules\User\Api\Account;

use Modules\User\Controllers\AuthController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;

/**
 * User Auth API Routes
 */
router()
	->middleware([
		CrossSiteProtectionMiddleware::class
	])
	->resource('user/auth', AuthController::class)
	->post('user/auth/login', [AuthController::class, 'login'])
	->post('user/auth/logout', [AuthController::class, 'logout'])
	->post('user/auth/register', [AuthController::class, 'register'])
	->post('user/auth/multifactor/get-auth-code', [AuthController::class, 'getAuthCode'])
	->post('user/auth/multifactor/verify-auth-code', [AuthController::class, 'verifyAuthCode'])
	->get('user/auth/csrf-token', [AuthController::class, 'getToken']);