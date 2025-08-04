<?php declare(strict_types=1);
namespace Modules\Developer\Api\Push;

use Modules\Developer\Controllers\PushController;

/**
 * Push Routes
 *
 * This file contains the API routes for the Push module.
 */
router()
	->get('developer/push/preview', [PushController::class, 'preview'])
	->post('developer/push/test', [PushController::class, 'test']);