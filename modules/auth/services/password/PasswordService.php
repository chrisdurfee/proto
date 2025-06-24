<?php declare(strict_types=1);
namespace Modules\Auth\Services\Password;

use Modules\User\Models\User;
use Modules\Auth\Models\PasswordRequest;
use Proto\Dispatch\Dispatcher;
use Modules\Auth\Auth\Gates\PasswordRequestGate;
use Proto\Controllers\Response;

/**
 * PasswordService
 *
 * This will set up the password service.
 *
 * @package Modules\Auth\Services\Password
 */
class PasswordService
{
	/** @var PasswordRequestGate|null */
	protected static ?PasswordRequestGate $gate = null;

	/**
	 * Lazy-load the password request gate.
	 *
	 * @return PasswordRequestGate
	 */
	protected static function gate(): PasswordRequestGate
	{
		return self::$gate ?? (self::$gate = new PasswordRequestGate());
	}

	/**
	 * This will get the password model.
	 *
	 * @return PasswordRequest
	 */
	protected function model(): PasswordRequest
	{
		return new PasswordRequest();
	}

	/**
	 * This will get the user by ID.
	 *
	 * @param mixed $userId
	 * @return User|null
	 */
	protected function getUserById(int $userId): ?User
	{
		return modules()->user()->get($userId);
	}

	/**
	 * This will display the error message.
	 *
	 * @param string $message
	 * @return Response
	 */
	protected function error(string $message): Response
	{
		$response = new Response();
		$response->error($message);
		return $response->format();
	}

	/**
	 * Kick off a new password reset request for the given user.
	 *
	 * @param User $user
	 * @return object
	 */
	public function sendResetRequest(User $user): object
	{
		/**
		 * This will add the password request to the database.
		 */
		$model = $this->model();
		$model->set('userId', $user->id);
		$model->add();

		/**
		 * This will set the request ID in the session.
		 */
		$requestId = $model->requestId;
		self::gate()->setRequest($requestId, $user->id);

		return $this->dispatchRequest($user, $requestId);
	}

	/**
	 * Route the reset request through email or SMS.
	 *
	 * @param User $user
	 * @param string $requestId
	 * @return object
	 */
	protected function dispatchRequest(User $user, string $requestId): object
	{
		$result = (object)[
			'email' => null,
			'sms' => null
		];

		if (!empty($user->mobile))
		{
			$result->sms = $this->textRequest($user, $requestId);
		}

		if (!empty($user->email))
		{
			$result->email = $this->emailRequest($user, $requestId);
		}

		return $result;
	}

	/**
	 * Send the reset link/code via email.
	 *
	 * @param User $user
	 * @param string $requestId
	 * @return object
	 */
	protected function emailRequest(User $user, string $requestId): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Password Reset Request',
			'template' => 'Modules\\Auth\\Email\\Password\\PasswordResetRequestEmail'
		];
		$data = (object)[
			'username' => $user->username,
			'resetUrl' => $this->buildResetUrl($requestId, $user->id)
		];

		return $this->dispatchEmail($settings, $data);
	}

	/**
	 * Send the reset link/code via SMS.
	 *
	 * @param User $user
	 * @param string $requestId
	 * @return object
	 */
	protected function textRequest(User $user, string $requestId): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'template' => 'Modules\\Auth\\Text\\Password\\PasswordResetRequestText'
		];
		$data = (object)[
			'code' => $requestId,
			'resetUrl' => $this->buildResetUrl($requestId, $user->id)
		];

		return $this->dispatchText($settings, $data);
	}

	/**
	 * Build the public URL for resetting.
	 *
	 * @param string $requestId
	 * @param mixed $userId
	 * @return string
	 */
	protected function buildResetUrl(string $requestId, mixed $userId): string
	{
		return envUrl()
			. '/login/auth/password-reset?requestId='
			. $requestId
			. '&userId='
			. (string)$userId;
	}

	/**
	 * Validate the password reset request.
	 *
	 * @param string $requestId
	 * @param mixed $userId
	 * @return string|null
	 */
	public function validateRequest(string $requestId, mixed $userId): ?string
	{
		return self::gate()->validateRequest($requestId, $userId);
	}

	/**
	 * Complete the password reset.
	 *
	 * @param string $requestId
	 * @param mixed $userId
	 * @param string $newPassword
	 * @param string $type
	 * @return bool
	 */
	public function resetPassword(
		string $requestId,
		mixed $userId,
		string $newPassword,
		string $type = 'email'
	): bool
	{
		$username = $this->validateRequest($requestId, $userId);
		if ($username === null)
		{
			return false;
		}

		$user = $this->getUserById($userId);
		if ($user === null)
		{
			return false;
		}

		$result = $user->updatePassword($newPassword);
		if (!$result)
		{
			return false;
		}

		self::gate()->resetRequest($requestId);

		$this->dispatchSuccess($user, $type);
		return true;
	}

	/**
	 * Route the success notification through email or SMS.
	 *
	 * @param User $user
	 * @param string $type
	 * @return object
	 */
	protected function dispatchSuccess(User $user, string $type): object
	{
		$result = (object)[
			'email' => null,
			'sms' => null
		];

		if (!empty($user->mobile))
		{
			$result->sms = $this->textSuccess($user);
		}

		if (!empty($user->email))
		{
			$result->email = $this->emailSuccess($user);
		}

		return $result;
	}

	/**
	 * Email confirmation of a successful password reset.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function emailSuccess(User $user): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Your Password Has Been Reset',
			'template' => 'Modules\\Auth\\Email\\Password\\PasswordResetSuccessEmail'
		];

		return $this->dispatchEmail($settings);
	}

	/**
	 * SMS confirmation of a successful password reset.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function textSuccess(User $user): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'template' => 'Modules\\Auth\\Text\\Password\\PasswordResetSuccessText'
		];

		return $this->dispatchText($settings);
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

	/**
	 * Queue an SMS via the app’s enqueue system.
	 *
	 * @param object $settings
	 * @param object|null $data
	 * @return object
	 */
	protected function dispatchText(object $settings, ?object $data = null): object
	{
		$settings->queue = true;
		return Dispatcher::sms($settings, $data);
	}
}
