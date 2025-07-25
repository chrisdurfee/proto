<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class FollowerPolicy
 *
 * Policy that governs access control for managing followers.
 *
 * @package Modules\User\Auth\Policies
 */
class FollowerPolicy extends Policy
{
	/**
	 * Determines if the user can list all users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function unfollow(Request $request): bool
	{
		$followerId = $request->params()->followerId ?? null;
		return $this->ownsResource($followerId);
	}

	/**
	 * Determines if the user can list all users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function follow(Request $request): bool
	{
		$followerId = $request->params()->followerId ?? null;
		return $this->ownsResource($followerId);
	}

	/**
	 * Determines if the user can toggle their follow status.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can toggle their follow status, otherwise false.
	 */
	public function toggle(Request $request): bool
	{
		$followerId = $request->params()->followerId ?? null;
		return $this->ownsResource($followerId);
	}

	/**
	 * Determines if the user can receive notifications for new followers.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can receive notifications, otherwise false.
	 */
	public function notify(Request $request): bool
	{
		$followerId = $request->params()->followerId ?? null;
		return $this->ownsResource($followerId);
	}

	/**
	 * Determines if the user can list all users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function all(Request $request): bool
	{
		return true;
	}
}