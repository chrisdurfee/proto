<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Models\User;
use Modules\Auth\Services\Auth\MultiFactorAuthService;
use Modules\Auth\Services\Password\PasswordService;
use Proto\Controllers\Controller;
use Proto\Http\Router\Request;

/**
 * PasswordController
 *
 * Handles password management, including resets and updates.
 *
 * @package Modules\Auth\Controllers
 */
class PasswordController extends Controller
{
    use AuthTrait;

	/**
	 * Constructor.
	 *
	 * @param MultiFactorAuthService $mfaService
	 * @param PasswordService $pwService
	 * @return void
	 */
	public function __construct(
		protected PasswordService $pwService = new PasswordService(),
	)
	{
		parent::__construct();
		$this->user = modules()->user();
	}

	/**
	 * Request a password reset.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function requestPasswordReset(Request $req): object
	{
		$email = $req->input('email');
		if (!isset($email))
		{
			return $this->error('The email is missing.', HttpStatus::BAD_REQUEST->value);
		}

		$user = $this->user->getByEmail($email);
		if (!$user)
		{
			return $this->error('The user is not found.', HttpStatus::NOT_FOUND->value);
		}

		$result = $this->pwService->sendResetRequest($user);
		if (empty($result->email) && empty($result->sms))
		{
			return $this->error('The password reset request has failed.', HttpStatus::BAD_REQUEST->value);
		}

		return $this->response((object)[
			'message' => 'The password reset request has been sent successfully.'
		]);
	}

	/**
	 * Validate the password request.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function validatePasswordRequest(Request $req): object
	{
		$requestId = $req->input('requestId');
		$userId = $req->getInt('userId');
		if (!isset($requestId) || !isset($userId))
		{
			return $this->error('The request id or user id is missing.', HttpStatus::BAD_REQUEST->value);
		}

		$username = $this->pwService->validateRequest($requestId, $userId);
		if ($username === null)
		{
			return $this->error('No request is found.', HttpStatus::NOT_FOUND->value);
		}

		return $this->response((object)[
			'username' => $username
		]);
	}

	/**
	 * Reset the password for a user.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function resetPassword(Request $req): object
	{
		$userId = $req->getInt('userId');
		if (!isset($userId))
		{
			return $this->error('The user id is not set.', HttpStatus::BAD_REQUEST->value);
		}

		$password = $req->input('password');
		if (empty($password))
		{
			return $this->error('The password is not set.', HttpStatus::BAD_REQUEST->value);
		}

		$requestId = $req->input('requestId');
		if (empty($requestId))
		{
			return $this->error('The request id is not set.', HttpStatus::BAD_REQUEST->value);
		}

		$result = $this->pwService->resetPassword($requestId, $userId, $password);
		if ($result === -1)
		{
			return $this->error('The password reset request is invalid.', HttpStatus::BAD_REQUEST->value);
		}

		if ($result === false)
		{
			return $this->error('The password reset has failed.', HttpStatus::BAD_REQUEST->value);
		}

		$user = $this->getUserId($userId);
		if (!$user)
		{
			return $this->error('The user account is not found.', HttpStatus::NOT_FOUND->value);
		}

		return $this->permit($user, $req->ip());
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
