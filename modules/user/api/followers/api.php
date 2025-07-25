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
	->group('user/:id/followers', function(Router $router)
	{
		$router->get(':followerId', [FollowerController::class, 'follow']);
		$router->post(':followerId/notify', [FollowerController::class, 'notify']);
		$router->put(':followerId/toggle', [FollowerController::class, 'toggle']);
		$router->delete(':followerId', [FollowerController::class, 'unfollow']);
	});

router()
	->get('user/:id/followers', [FollowerController::class, 'all']);