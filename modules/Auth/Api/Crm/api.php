<?php declare(strict_types=1);
namespace Modules\Auth\Api;

use Modules\Auth\Controllers\PasswordController;
use Modules\Auth\Controllers\CrmAuthController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Middleware\ThrottleMiddleware;
use Proto\Http\Router\Router;
use Proto\Http\Middleware\DomainMiddleware;

/**
 * Auth Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class
	]))
	->post('auth/crm/pulse', [CrmAuthController::class, 'pulse'])
	->get('auth/crm/csrf-token', [CrmAuthController::class, 'getToken'], [
		DomainMiddleware::class
	]);

router()
	->middleware(([
		ThrottleMiddleware::class,
	]))
	->group('auth/crm', function(Router $router)
	{
		$controller = new CrmAuthController();
		// standard login / logout / register
		$router->post('login', [$controller, 'login']);
		$router->post('logout', [$controller, 'logout']);
		$router->post('resume', [$controller, 'resume']);
		$router->post('pulse', [$controller, 'pulse']);

		// MFA: send & verify one‑time codes
		$router->post('mfa/code', [$controller, 'getAuthCode']);
		$router->post('mfa/verify', [$controller, 'verifyAuthCode']);

		// Password reset: request & verify reset codes
		$controller = new PasswordController();
		$router->post('password/request', [$controller, 'requestPasswordReset']);
		$router->post('password/verify', [$controller, 'validatePasswordRequest']);
		$router->post('password/reset', [$controller, 'resetPassword']);
	});
