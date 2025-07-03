<?php declare(strict_types=1);
namespace Modules\User\Services\User;

use Modules\User\Models\User;
use Modules\User\Email\Password\PasswordUpdatedEmail;
use Proto\Dispatch\Dispatcher;

/**
 * PasswordUpdateService
 *
 * Handles updating a user's password and username,
 * and sends notification emails when appropriate.
 *
 * @package Modules\User\Services\User
 */
class PasswordUpdateService
{
	/**
	 * Update user credentials.
	 *
	 * @param object $data
	 * @return object
	 */
	public function updateCredentials(object $data): object
	{
		$response = (object)[
			'username' => null,
			'password' => null
		];

		$userId = $data->id ?? null;
		if (!isset($userId))
		{
			return $response;
		}

		$user = User::get($userId);
		if (!$user)
		{
			return $response;
		}

		if (!empty($data->username))
		{
			if ($data->username !== $user->username)
			{
				$response->username = $this->updateUsername($user, $data->username);
			}
		}

		if (!empty($data->password))
		{
			$response->password = $this->updatePassword($user, $data->password);
		}

		return $response;
	}

	/**
	 * Update the user's password and send a notification email.
	 *
	 * @param User $user
	 * @param string $newPassword
	 * @return object|null
	 */
	public function updatePassword(User $user, string $newPassword): ?object
	{
		$success = $user->updatePassword($newPassword);
		if (!$success)
		{
			return null;
		}

		return $this->sendPasswordEmail($user);
	}

	/**
	 * Send a “password updated” email to the user.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function sendPasswordEmail(User $user): object
	{
		$siteName = env('siteName');
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Your password was updated on ' . $siteName,
			'template' => PasswordUpdatedEmail::class
		];

		$data = (object)[
			'username' => $user->username,
			'siteName' => $siteName
		];

		return $this->dispatchEmail($settings, $data);
	}

	/**
	 * Update the user's username.
	 *
	 * @param User $user
	 * @param string $newUsername
	 * @return bool|int
	 */
	public function updateUsername(User $user, string $newUsername): bool|int
	{
		return $user->updateUsername($newUsername);
	}

	/**
	 * Queue and dispatch an email via the app's dispatcher.
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