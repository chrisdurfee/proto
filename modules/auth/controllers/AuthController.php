<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Gateway\Gateway;
use Modules\User\Models\User;
use Modules\Auth\Models\LoginLog;
use Modules\Auth\Controllers\LoginAttemptController;
use Modules\Auth\Controllers\UserStatus;
use Modules\Auth\Services\Auth\MultiFactorAuthService;
use Modules\Auth\Controllers\Multifactor\MultiFactorHelper;
use Modules\Auth\Services\Password\PasswordService;
use Proto\Controllers\Controller;
use Proto\Http\Router\Request;
use Proto\Auth\Gates\CrossSiteRequestForgeryGate;

/**
 * AuthController
 *
 * Handles user login, logout, registration, MFA flows, and CSRF token.
 *
 * @package Modules\Auth\Controllers
 */
class AuthController extends Controller
{
	/**
	 * Maximum failed login attempts allowed.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 10;

	/**
	 * @var Gateway
	 */
	protected Gateway $user;

	/**
	 * Constructor.
	 *
	 * @param MultiFactorAuthService $mfaService
	 * @param PasswordService $pwService
	 * @return void
	 */
	public function __construct(
		protected MultiFactorAuthService $mfaService = new MultiFactorAuthService(),
		protected PasswordService $pwService = new PasswordService(),
	)
	{
		parent::__construct();
		$this->user = modules()->user();
	}

	/**
	 * Handle a login request.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function login(Request $req): object
	{
		$username = $req->input('username');
		$password = $req->input('password');
		if (!$username || !$password)
		{
			return $this->error('The username and password are required.', HttpStatus::BAD_REQUEST->value);
		}

		$attempts = $this->getAttempts($username, $req->ip());
		if ($attempts >= self::MAX_ATTEMPTS)
		{
			return $this->error('Maximum login attempts reached. Please try again later.', HttpStatus::TOO_MANY_REQUESTS->value);
		}

		$userId = $this->authenticate($username, $password, $req->ip());
		if ($userId < 0)
		{
			return $this->error('Invalid credentials. Attempt ' . ++$attempts . ' of ' . self::MAX_ATTEMPTS, HttpStatus::UNAUTHORIZED->value);
		}

		$user = $this->getUserId($userId);
		if (!$user)
		{
			return $this->error('The user account is not found.', HttpStatus::NOT_FOUND->value);
		}

		if ($user->multiFactor === true)
		{
			$device = $req->json('device');
			return $this->multiFactor($user, $device);
		}

		return $this->permit($user);
	}

	/**
	 * This will permit a user access to sign in.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function permit(User $user): object
	{
		$this->updateStatus($user, UserStatus::ONLINE->value);
		$this->setSessionUser($user);

		return $this->response([
			'allowAccess' => true,
			'user' => $user->getData()
		]);
	}

	/**
	 * Handle the MFA step.
	 *
	 * @param User $user
	 * @param object|null $device
	 * @return object
	 */
	protected function multiFactor(User $user, ?object $device): object
	{
		$this->mfaService->setResources($user, $device);

		if (MultiFactorHelper::isDeviceAuthorized($user, $device))
		{
			return $this->permit($user);
		}

		$options = MultiFactorHelper::getMultiFactorOptions($user);

		return $this->response([
			'allowAccess' => false,
			'multiFactor' => true,
			'options' => $options
		]);
	}

	/**
	 * Send or resend an MFA code.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function getAuthCode(Request $req): object
	{
		$user = $this->mfaService->getUser();
		if (!$user)
		{
			return $this->error('The user not found in MFA session.', HttpStatus::NOT_FOUND->value);
		}

		$type = $req->input('type', 'sms');
		$this->mfaService->sendCode($user, $type);

		return $this->response(['success' => true]);
	}

	/**
	 * Validate the submitted MFA code.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function verifyAuthCode(Request $req): object
	{
		$user = $this->mfaService->getUser();
		if (!$user)
		{
			return $this->error('The user is not found in MFA session.', HttpStatus::NOT_FOUND->value);
		}

		$device = $this->mfaService->getDevice();
		if (!$device)
		{
			return $this->error('The device is not found in MFA session.', HttpStatus::NOT_FOUND->value);
		}

		$code = $req->input('code');
		$isValid = $this->mfaService->validateCode($code);
		if ($isValid === false)
		{
			return $this->error('Invalid authentication code.', HttpStatus::UNAUTHORIZED->value);
		}

		if ($isValid === null)
		{
			return $this->error('Invalid authentication code. Too many attempts.', HttpStatus::TOO_MANY_REQUESTS->value);
		}

		$this->mfaService->addNewConnection($user, $device, $req->ip());

		return $this->permit($user);
	}

	/**
	 * Logout the current user.
	 *
	 * @return object
	 */
	public function logout(): object
	{
		$session = getSession('user');
		$userId = $session->id ?? null;
		if (!$userId)
		{
			return $this->error('The user is not authenticated.', HttpStatus::UNAUTHORIZED->value);
		}

		$user = $this->user->get($userId);
		if (!$user)
		{
			return $this->error('The user is not found.', HttpStatus::NOT_FOUND->value);
		}

		$this->updateStatus($user->id, UserStatus::OFFLINE->value);
		session()->destroy();

		return $this->response(['message' => 'The user has been logged out successfully.']);
	}

	/**
	 * Resume a user session.
	 *
	 * @return object
	 */
	public function resume(): object
	{
		$session = getSession('user');
		$userId = $session->id ?? null;
		if (!$userId)
		{
			return $this->error('The user is not authenticated.', HttpStatus::UNAUTHORIZED->value);
		}

		$user = $this->user->get($userId);
		if (!$user)
		{
			return $this->error('The user is not found.', HttpStatus::NOT_FOUND->value);
		}

		if ($user->enabled === 0)
		{
			return $this->error('The user is not enabled.', HttpStatus::FORBIDDEN->value);
		}

		return $this->permit($user);
	}

	/**
	 * Pulse the user session to keep it alive.
	 *
	 * @return object
	 */
	public function pulse(): object
	{
		$session = getSession('user');
		$userId = $session->id ?? null;
		if (!$userId)
		{
			return $this->error('The user is not authenticated.', HttpStatus::UNAUTHORIZED->value);
		}

		$user = $this->user->get($userId);
		if (!$user)
		{
			return $this->error('The user is not found.', HttpStatus::NOT_FOUND->value);
		}

		if ($user->enabled === 0)
		{
			return $this->error('The user is not enabled.', HttpStatus::FORBIDDEN->value);
		}

		return $this->permit($user);
	}

	/**
	 * Register a new user.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function register(Request $req): object
	{
		$data = $req->json('user');
		if (!$data)
		{
			return $this->error('The data is invalid for registration.', HttpStatus::BAD_REQUEST->value);
		}

		$user = $this->user->register($data);
		if (!$user)
		{
			return $this->error('The registration has failed.', HttpStatus::BAD_REQUEST->value);
		}

		return $this->permit($user);
	}

	/**
	 * Retrieve a fresh CSRF token.
	 *
	 * @return object
	 */
	public function getToken(): object
	{
		$token = (new CrossSiteRequestForgeryGate())->setToken();
		return $this->response(['token' => $token]);
	}

	/**
	 * Authenticate credentials and log failed attempts.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $ipAddress
	 * @return int
	 */
	protected function authenticate(string $username, string $password, string $ipAddress): int
	{
		$userId = $this->user->authenticate($username, $password);
		if ($userId < 0)
		{
			LoginAttemptController::create((object)[
				'ipAddress' => $ipAddress,
				'username' => $username
			]);
		}

		return $userId;
	}

	/**
	 * This will update the user status.
	 *
	 * @param User $user
	 * @param string $status
	 * @return bool
	 */
	protected function updateStatus(User $user, string $status): bool
	{
		$success = $this->user->updateStatus($user->id, $status);
		if (!$success)
		{
			return false;
		}

		return $this->updateLoginStatus($user->id, $status);
	}

	/**
	 * Update login status (login/logout) in LoginLog.
	 *
	 * @param int|string $userId
	 * @param string $status
	 * @return bool
	 */
	protected function updateLoginStatus(int|string $userId, string $status): bool
	{
		if ($status !== UserStatus::ONLINE->value && $status !== UserStatus::OFFLINE->value)
		{
			return false;
		}

		$direction = $status === UserStatus::ONLINE->value ? 'login' : 'logout';
		return LoginLog::create((object)[
			'dateTimeSetup' => date('Y-m-d H:i:s'),
			'userId' => $userId,
			'direction' => $direction
		]);
	}

	/**
	 * Count recent failed login attempts.
	 *
	 * @param string $username
	 * @param string $ipAddress
	 * @return int
	 */
	protected function getAttempts(string $username, string $ipAddress): int
	{
		return LoginAttemptController::countAttempts($ipAddress, $username);
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
		$user = $req->json('user');
		if (!isset($user))
		{
			return $this->error('The user is not set.', HttpStatus::BAD_REQUEST->value);
		}

		if (empty($user->password))
		{
			return $this->error('The password is not set.', HttpStatus::BAD_REQUEST->value);
		}

		$requestId = $user->requestId;
		$userId = $user->userId;

		$result = $this->pwService->resetPassword($requestId, $userId, $user->password);
		return $this->response((object)[
			'message' => ($result)?'The password has been reset successfully.' : 'The password reset has failed.',
		]);
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
