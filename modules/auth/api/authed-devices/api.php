<?php declare(strict_types=1);
namespace Modules\Auth\Api;

use Modules\Auth\Controllers\UserAuthedDeviceController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Router\Router;

/**
 * Auth API Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class
	]))
	->group('auth/authed-devices', function(Router $router)
	{
		$controller = new UserAuthedDeviceController();
		$router->get(':userId', [$controller, 'all']);
	});
