<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\GeneratorController;

/**
 * Generator Routes
 *
 * This file contains the API routes for the Generator module.
 */
router()
	->post('developer/generator', [GeneratorController::class, 'addType']);