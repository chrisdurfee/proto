<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Modules\User\Models\User;
use Modules\Auth\Controllers\LoginAttemptController;
use Modules\Auth\Controllers\UserStatus;
use Modules\Auth\Services\Auth\MultiFactorAuthService;
use Modules\Auth\Controllers\Multifactor\MultiFactorHelper;
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
	use AuthTrait;

	/**
	 * Maximum failed login attempts allowed.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 10;

	/**
	 * Constructor.
	 *
	 * @param MultiFactorAuthService $mfaService
	 * @return void
	 */
	public function __construct(
		protected MultiFactorAuthService $mfaService = new MultiFactorAuthService(),
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

		if ($user->multiFactorEnabled == true)
		{
			$device = $req->json('device');
			return $this->multiFactor($user, $device, $req->ip());
		}

		return $this->permit($user, $req->ip());
	}

	/**
	 * Handle the MFA step.
	 *
	 * @param User $user
	 * @param object|null $device
	 * @param string $ip
	 * @return object
	 */
	protected function multiFactor(User $user, ?object $device, string $ip): object
	{
		$this->mfaService->setResources($user, $device);

		if (MultiFactorHelper::isDeviceAuthorized($user, $device))
		{
			return $this->permit($user, $ip);
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

		$ipAddress = $req->ip();
		$this->mfaService->addNewConnection($user, $device, $ipAddress);

		return $this->permit($user, $ipAddress);
	}

	/**
	 * Logout the current user.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function logout(Request $req): object
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

		$this->updateUserStatus($user, UserStatus::OFFLINE->value, $req->ip());
		session()->destroy();

		return $this->response(['message' => 'The user has been logged out successfully.']);
	}

	/**
	 * Resume a user session.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function resume(Request $req): object
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

		/**
		 * Check if the connection is still active before refreshing the session ID.
		 */
		if(connection_aborted() === false)
		{
			// refresh session ID to prevent fixation
			session()->refreshId();
		}

		return $this->permit($user, $req->ip());
	}

	/**
	 * Pulse the user session to keep it alive.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function pulse(Request $req): object
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

		return $this->permit($user, $req->ip());
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

		return $this->permit($user, $req->ip());
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
}
