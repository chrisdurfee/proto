<?php declare(strict_types=1);
namespace Modules\User\Api\Followers;

use Modules\User\Controllers\FollowerController;

/**
 * User Followers Routes
 *
 * This will handle the API routes for the User followers.
 */
router()
	->resource('user/:userId/followers', FollowerController::class);