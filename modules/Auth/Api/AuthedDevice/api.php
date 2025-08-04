<?php declare(strict_types=1);
namespace Modules\Auth\Api\AuthedDevice;

use Modules\Auth\Controllers\UserAuthedDeviceController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Router\Router;

/**
 * Authed Device Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class
	]))
	->group('auth/:userId', function(Router $router)
	{
		$controller = new UserAuthedDeviceController();
		$router->get('authed-device', [$controller, 'all']);
	});
