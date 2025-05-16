<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\MigrationController;
use Proto\Http\Router\Router;

/**
 * Migration Routes
 *
 * This file contains the API routes for the Migration module.
 */
router()
	->post('developer/migration', [MigrationController::class, 'apply'])
	->group('developer', function(Router $router)
	{
		$router
			//->post('migration', [MigrationController::class, 'apply'])
			->get('migration*', [MigrationController::class, 'all']);
	});