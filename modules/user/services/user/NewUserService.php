<?php declare(strict_types=1);
namespace Modules\User\Services\User;

use Modules\User\Email\Welcome\WelcomeVerificationEmail;
use Modules\User\Models\User;
use Modules\User\Models\RoleUser;
use Modules\User\Models\Role;
use Modules\User\Models\EmailVerification;
use Proto\Dispatch\Dispatcher;

/**
 * NewUserService
 *
 * Sends welcome and verification emails to newly registered users.
 *
 * @package Modules\User\Services\User
 */
class NewUserService
{
	/**
	 * Send verification email to new user.
	 *
	 * @param User $user
	 * @return object
	 */
	public function sendVerification(User $user): object
	{
		$token = $this->createVerification($user);
		return $this->emailVerification($user, $token);
	}

	/**
	 * This will create a new user and send the verification email.
	 *
	 * @param object $data
	 * @return User
	 */
	public function createUser(object $data): User
	{
		$user = $this->addUser($data);
		if (!$user)
		{
			return $user;
		}

		if (!$this->addRoles($user))
		{
			return $user;
		}

		if (!$this->sendVerification($user))
		{
			return $user;
		}

		return $user;
	}

	/**
	 * This will add a new user to the database.
	 *
	 * @param object $data
	 * @return User
	 */
	protected function addUser(object $data): User
	{
		$model = new User($data);
		$model->add();
		return $model;
	}

	/**
	 * This will add the roles to the user.
	 *
	 * @param User $user
	 * @return bool
	 */
	protected function addRoles(User $user): bool
	{
		$success = true;
		$roles = [
			'user',
		];

		foreach ($roles as $role)
		{
			$result = $this->addRole($user, $role) && $success;
			if (!$result)
			{
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * This will add the role to the user.
	 *
	 * @param User $user
	 * @param string $role
	 * @return bool
	 */
	protected function addRole(User $user, string $role): bool
	{
		$role = (new Role())->getBySlug($role);
		if (!$role)
		{
			return false;
		}

		$model = new RoleUser((object)[
			'userId' => $user->id,
			'roleId' => $role->id
		]);
		return $model->add();
	}

	/**
	 * Generate and store email verification token.
	 *
	 * @param User $user
	 * @return string
	 */
	protected function createVerification(User $user): string
	{
		$model = new EmailVerification();
		$model->set('userId', $user->id);
		$model->add();

		return $model->requestId;
	}

	/**
	 * Send the verification email.
	 *
	 * @param User $user
	 * @param string $token
	 * @return object
	 */
	protected function emailVerification(User $user, string $token): object
	{
		$siteName = env('siteName');
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Welcome to ' . $siteName . '! Please verify your email',
			'template' => WelcomeVerificationEmail::class
		];
		$data = (object)[
			'username' => $user->username,
			'verifyUrl' => $this->buildVerifyUrl($token, $user->id)
		];

		return $this->dispatchEmail($settings, $data);
	}

	/**
	 * Build the public URL for email verification.
	 *
	 * @param string $token
	 * @param mixed $userId
	 * @return string
	 */
	protected function buildVerifyUrl(string $token, mixed $userId): string
	{
		return envUrl() . '/verify-email?token=' . $token . '&userId=' . (string)$userId;
	}

	/**
	 * Queue an email via the app’s enqueue system.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return object
	 */
	protected function dispatchEmail(object $settings, ?object $data = null): object
	{
		$settings->queue = true;
		return Dispatcher::email($settings, $data);
	}
}
