<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\ErrorController;
use Modules\Developer\Controllers\MigrationController;
use Proto\Http\Router\Router;

/**
 * Migration Routes
 *
 * This file contains the API routes for the Migration module.
 */
router()
	->group('developer/error', function(Router $router)
	{
		$router
			->post('', [ErrorController::class, 'toggleResolve'])
			->get('*', [ErrorController::class, 'all']);
	});