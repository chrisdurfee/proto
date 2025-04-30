<?php declare(strict_types=1);
namespace Modules\User\Services\User;

use Modules\User\Models\User;
use Modules\User\Models\EmailVerification;
use Proto\Dispatch\Enqueuer;

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

		return $model->token;
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
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Welcome to OurApp! Please verify your email',
			'template' => 'Modules\\User\\Email\\Welcome\\WelcomeVerificationEmail'
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
		return envUrl() . '/verify?token=' . $token . '&userId=' . (string)$userId;
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
		return Enqueuer::email($settings, $data);
	}
}
