<?php declare(strict_types=1);
namespace Modules\Developer\Api\Table;

use Modules\Developer\Controllers\TableController;

/**
 * Table Routes
 *
 * This file contains the API routes for the Table module.
 */
router()
	->get('developer/table/columns*', [TableController::class, 'getColumns']);