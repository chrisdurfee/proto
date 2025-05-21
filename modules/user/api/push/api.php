<?php declare(strict_types=1);
namespace Modules\User\Api\Push;

use Modules\User\Controllers\WebPushController;

/**
 * Push API Routes
 *
 * This file contains the API routes for the push notifications.
 */
router()
	->post('push/subscribe', [WebPushController::class, 'subscribe'])
    ->post('push/unsubscribe', [WebPushController::class, 'unsubscribe']);