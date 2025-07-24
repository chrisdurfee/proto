<?php declare(strict_types=1);
namespace Modules\User\Api\Followers;

use Modules\User\Controllers\FollowerController;
use Proto\Http\Router\Router;

/**
 * User Followers Routes
 *
 * This will handle the API routes for the User followers.
 */
router()
	->group('auth/:userId', function(Router $router)
	{
		$router->post('follow', [FollowerController::class, 'follow']);
		$router->post('followers/:followerId/notify', [FollowerController::class, 'notify']);
		$router->put('followers/:followerId/toggle', [FollowerController::class, 'toggle']);
		$router->delete('followers/:followerId', [FollowerController::class, 'unfollow']);
	});