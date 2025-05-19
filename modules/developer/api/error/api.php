<?php declare(strict_types=1);
namespace Modules\Developer\Api\Error;

use Modules\Developer\Controllers\ErrorController;
use Proto\Http\Router\Router;

/**
 * Error Routes
 *
 * This file contains the API routes for the Error module.
 */
router()
	->group('developer', function(Router $router)
	{
		$router
			->patch('error', [ErrorController::class, 'toggleResolve'])
			->get('error*', [ErrorController::class, 'all']);
	});