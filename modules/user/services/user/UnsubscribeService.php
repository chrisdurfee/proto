<?php declare(strict_types=1);
namespace Modules\User\Services\User;

use Modules\User\Models\User;
use Proto\Dispatch\Email\Unsubscribe\Models\Unsubscribe;
use Modules\User\Models\NotificationPreference;

/**
 * UnsubscribeService
 *
 * Handles user unsubscription requests.
 *
 * @package Modules\User\Services\User
 */
class UnsubscribeService
{
	/**
	 * Update the user's notification preferences.
	 *
	 * @param object $data
	 * @return object
	 */
	protected function updateNotificationPreferences(object $data): bool
	{
		return NotificationPreference::put($data);
	}

	/**
	 * Verify the unsubscribe request.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function verifyUnsubscribeRequest(object $data): bool
	{
		$result = Unsubscribe::getByRequest($data->requestId, $data->email);
		return (!empty($result));
	}

	/**
	 * Get the user by email.
	 *
	 * @param string $email
	 * @return User|null
	 */
	protected function getUserByEmail(string $email): ?User
	{
		return User::getByEmail($email);
	}

	/**
	 * This will verify the unsubscribe request and update the user's preferences.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function unsubscribe(object $settings): bool
	{
		$data = (object)[
			'email' => $settings->email,
			'requestId' => $settings->requestId
		];

		if (!$this->verifyUnsubscribeRequest($data))
		{
			return false;
		}

		$user = $this->getUserByEmail($data->email);
		if (!$user)
		{
			return false;
		}

		$data->userId = $user->id;
		$allowEmail = $settings->allowEmail ?? 0;
		if (isset($allowEmail))
		{
			$data->allowEmail = $allowEmail;
		}

		$allowSms = $settings->allowSms ?? 0;
		if (isset($allowSms))
		{
			$data->allowSms = $allowSms;
		}

		$allowPush = $settings->allowPush ?? 0;
		if (isset($allowPush))
		{
			$data->allowPush = $allowPush;
		}

		return $this->updateNotificationPreferences($data);
	}
}
