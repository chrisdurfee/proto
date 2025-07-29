<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class BlockUserPolicy
 *
 * Policy that governs access control for managing blocked users.
 *
 * @package Modules\User\Auth\Policies
 */
class BlockUserPolicy extends Policy
{
	/**
	 * Determines if the user can unblock a user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can unblock, otherwise false.
	 */
	public function unblock(Request $request): bool
	{
		$id = $this->getResourceId($request);
		return $this->ownsResource($id);
	}

	/**
	 * Determines if the user can block a user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can block, otherwise false.
	 */
	public function block(Request $request): bool
	{
		$id = $this->getResourceId($request);
		return $this->ownsResource($id);
	}

	/**
	 * Determines if the user can toggle their block status.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can toggle their block status, otherwise false.
	 */
	public function toggle(Request $request): bool
	{
		$id = $this->getResourceId($request);
		return $this->ownsResource($id);
	}

	/**
	 * Determines if the user can list all blocked users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function all(Request $request): bool
	{
		$id = $this->getResourceId($request);
		return $this->ownsResource($id);
	}
}