<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\TableController;

/**
 * Table Routes
 *
 * This file contains the API routes for the Table module.
 */
router()
	->post('developer/table/columns*', [TableController::class, 'getColumns']);