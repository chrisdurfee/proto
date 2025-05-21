<?php declare(strict_types=1);
namespace Modules\User\Api\Push;

use Modules\User\Controllers\WebPushController;
use Proto\Http\Router\Router;

/**
 * Push API Routes
 *
 * This file contains the API routes for the push notifications.
 */
router()
	->group('user/{id}/push', function(Router $router)
	{
		$router
			->post('subscribe', [WebPushController::class, 'subscribe'])
			->post('unsubscribe', [WebPushController::class, 'unsubscribe']);
	});