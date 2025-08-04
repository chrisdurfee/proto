<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Gateway\Gateway;
use Modules\User\Models\User;
use Modules\Auth\Models\LoginLog;
use Modules\Auth\Controllers\UserStatus;

/**
 * AuthTrait
 *
 * Handles user permitting and session management.
 *
 * @package Modules\Auth\Controllers
 */
trait AuthTrait
{
	/**
	 * @var Gateway
	 */
	protected Gateway $user;

	/**
	 * This will permit a user access to sign in.
	 *
	 * @param User $user
	 * @param string $ip
	 * @return object
	 */
	protected function permit(User $user, string $ip): object
	{
		$this->updateUserStatus($user, UserStatus::ONLINE->value, $ip);
		$this->setSessionUser($user);
		$this->setLastLogin($user);

		return $this->response([
			'allowAccess' => true,
			'user' => $user->getData()
		]);
	}

	/**
	 * This will set the last login time for the user.
	 *
	 * @param User $user
	 * @return bool
	 */
	protected function setLastLogin(User $user): bool
	{
		$user->lastLoginAt = date('Y-m-d H:i:s');
		return modules()->user()->update($user->getData());
	}

	/**
	 * This will update the user status.
	 *
	 * @param User $user
	 * @param string $status
	 * @param string $ip
	 * @return bool
	 */
	protected function updateUserStatus(User $user, string $status, string $ip): bool
	{
		$success = $this->user->updateStatus($user->id, $status);
		if (!$success)
		{
			return false;
		}

		return $this->updateLoginStatus($user->id, $status, $ip);
	}

	/**
	 * Update login status (login/logout) in LoginLog.
	 *
	 * @param int|string $userId
	 * @param string $status
	 * @param string $ip
	 * @return bool
	 */
	protected function updateLoginStatus(int|string $userId, string $status, string $ip): bool
	{
		if ($status !== UserStatus::ONLINE->value && $status !== UserStatus::OFFLINE->value)
		{
			return false;
		}

		$direction = $status === UserStatus::ONLINE->value ? 'login' : 'logout';
		return LoginLog::create((object)[
			'dateTimeSetup' => date('Y-m-d H:i:s'),
			'userId' => $userId,
			'direction' => $direction,
			'ip' => $ip
		]);
	}

	/**
	 * Retrieve a user by ID or null.
	 *
	 * @param mixed $userId
	 * @return User|null
	 */
	protected function getUserId(mixed $userId): ?User
	{
		return modules()->user()->get($userId);
	}

	/**
	 * Store the authenticated user in session.
	 *
	 * @param User $user
	 * @return void
	 */
	protected function setSessionUser(User $user): void
	{
		setSession('user', $user->getData());
	}
}
