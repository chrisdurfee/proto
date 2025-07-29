<?php declare(strict_types=1);
namespace Modules\User\Services\User;

use Modules\User\Models\User;
use Modules\User\Models\BlockUser;
use Modules\User\Push\NewFollowerPush;
use Proto\Controllers\Response;

/**
 * BlockUserService
 *
 * Handles block-related operations including blocking, unblocking,
 * toggling block status, and sending notifications.
 *
 * @package Modules\User\Services\User
 */
class BlockUserService
{
	/**
	 * Toggles the block status between a user and blocker.
	 *
	 * @param mixed $userId The user being blocked/unblocked
	 * @param mixed $blockerId The user doing the blocking/unblocking
	 * @return object
	 */
	public function toggleBlock(mixed $userId, mixed $blockerId): object
	{
		$user = User::get($userId);
		if (!$user)
		{
			return (object)['success' => false, 'error' => 'User not found.'];
		}

		$alreadyBlocked = $this->alreadyBlocked($userId, $blockerId);
		$result = $user->blockers()->toggle([$blockerId]);
		if (!$result)
		{
			return Response::invalid('Failed to toggle block status.');
		}

		return Response::success(['result' => $result]);
	}

	/**
	 * Check if a user is already blocked by the user.
	 *
	 * @param mixed $userId
	 * @param mixed $blockerId
	 * @return bool
	 */
	protected function alreadyBlocked(mixed $userId, mixed $blockerId): bool
	{
		return BlockUser::isAdded($userId, $blockerId);
	}

	/**
	 * Adds a block to a user.
	 *
	 * @param mixed $userId The user blocking
	 * @param mixed $blockerId The user being blocked
	 * @return object
	 */
	public function blockUser(mixed $userId, mixed $blockerId): object
	{
		$user = User::get($userId);
		if (!$user)
		{
			return Response::invalid('User not found.');
		}

		if ($this->alreadyBlocked($userId, $blockerId))
		{
			return Response::invalid('User is already blocked.');
		}

		$result = $user->blockers()->attach($blockerId);
		if (!$result)
		{
			return Response::invalid('Failed to block user.');
		}

		return Response::success(['result' => $result]);
	}

	/**
	 * Removes a follower from a user.
	 *
	 * @param mixed $userId The user unblocking
	 * @param mixed $blockerId The user being unblocked
	 * @return object
	 */
	public function unblockUser(mixed $userId, mixed $blockerId): object
	{
		$user = User::get($userId);
		if (!$user)
		{
			return Response::invalid('User not found.');
		}

		if (!$this->alreadyBlocked($userId, $blockerId))
		{
			return Response::invalid('User is not blocked.');
		}

		$result = $user->blockers()->detach($blockerId);
		if (!$result)
		{
			return Response::invalid('Failed to unblock user.');
		}

		return Response::success(['result' => $result]);
	}
}