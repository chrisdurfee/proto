<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\MigrationController;

/**
 * Migration Routes
 *
 * This file contains the API routes for the Migration module.
 */
router()
	->get('developer/migration*', [MigrationController::class, 'all']);