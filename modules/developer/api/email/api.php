<?php declare(strict_types=1);
namespace Modules\Developer\Api\Email;

use Modules\Developer\Controllers\EmailController;

/**
 * Email Routes
 *
 * This file contains the API routes for the Email module.
 */
router()
	->get('developer/email/preview', [EmailController::class, 'preview'])
	->post('developer/email/test', [EmailController::class, 'test']);