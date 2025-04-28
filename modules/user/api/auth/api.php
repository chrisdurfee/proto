<?php declare(strict_types=1);
namespace Modules\User\Api\Auth;

use Modules\User\Controllers\AuthController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Middleware\ThrottleMiddleware;
use Proto\Http\Router\Router;

/**
 * Auth API Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class,
		ThrottleMiddleware::class,
	]))
	->group('user/auth', function(Router $router)
	{
		$controller = new AuthController();
		// standard login / logout / register
		$router->post('login', [$controller, 'login']);
		$router->post('logout', [$controller, 'logout']);
		$router->post('register', [$controller, 'register']);

		// MFA: send & verify one‑time codes
		$router->post('mfa/code', [$controller, 'getAuthCode']);
		$router->post('mfa/verify', [$controller, 'verifyAuthCode']);

		// CSRF token (no body, safe to GET)
		$router->get('csrf-token', [$controller, 'getToken']);
	});
