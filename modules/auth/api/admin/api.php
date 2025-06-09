<?php declare(strict_types=1);
namespace Modules\Auth\Api;

use Modules\Auth\Controllers\AdminAuthController;
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
	->group('auth/admin', function(Router $router)
	{
		$controller = new AdminAuthController();
		// standard login / logout / register
		$router->post('login', [$controller, 'login']);
		$router->post('resume', [$controller, 'resume']);
		$router->post('pulse', [$controller, 'pulse']);
	});
