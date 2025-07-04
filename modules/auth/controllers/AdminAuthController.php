<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Models\User;
use Modules\Auth\Controllers\UserStatus;

/**
 * AdminAuthController
 *
 * Handles user login, logout, registration, MFA flows, and CSRF token for admin users.
 *
 * @package Modules\Auth\Controllers
 */
class AdminAuthController extends AuthController
{
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

		if (auth()->user->isAdmin() === false)
		{
			return $this->error('Access denied. Admin privileges required.', HttpStatus::FORBIDDEN->value);
		}

		return $this->response([
			'allowAccess' => true,
			'user' => $user->getData()
		]);
	}
}
