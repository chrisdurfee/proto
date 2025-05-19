<?php declare(strict_types=1);
namespace Modules\Developer\Api\Sms;

use Modules\Developer\Controllers\SmsController;

/**
 * SMS Routes
 *
 * This file contains the API routes for the SMS module.
 */
router()
	->get('developer/sms/preview', [SmsController::class, 'preview'])
	->post('developer/sms/test', [SmsController::class, 'test']);