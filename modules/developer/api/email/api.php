<?php declare(strict_types=1);
namespace Modules\Developer\Api;

use Modules\Developer\Controllers\EmailController;

/**
 * Email Routes
 *
 * This file contains the API routes for the Email module.
 */
router()
	->get('developer/email/preview', [EmailController::class, 'preview']);