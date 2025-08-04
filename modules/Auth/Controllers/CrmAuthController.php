<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Models\User;
use Modules\Auth\Controllers\UserStatus;

/**
 * CrmAuthController
 *
 * Handles user login, logout, registration, MFA flows, and CSRF token for CRM users.
 *
 * @package Modules\Auth\Controllers
 */
class CrmAuthController extends AuthController
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

		if (auth()->permission->hasPermission('crm.access') === false)
		{
			return $this->error('Access denied. CRM privileges required.', HttpStatus::FORBIDDEN->value);
		}

		return $this->response([
			'allowAccess' => true,
			'user' => $user->getData()
		]);
	}
}
