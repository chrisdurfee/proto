<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Modules\User\Models\LoginLog;
use Modules\User\Controllers\LoginAttemptController;
use Modules\User\Services\Auth\MultiFactorAuthService;
use Modules\User\Controllers\Multifactor\MultiFactorHelper;
use Modules\User\Services\Password\PasswordService;
use Proto\Controllers\Controller;
use Proto\Http\Request;
use Proto\Auth\Gates\CrossSiteRequestForgeryGate;

/**
 * UserStatus Enum
 *
 * This enum defines the possible user statuses.
 */
enum UserStatus: string
{
	case ONLINE  = 'online';
	case OFFLINE = 'offline';
	case BUSY    = 'busy';
	case AWAY    = 'away';
}

/**
 * AuthController
 *
 * Handles user login, logout, registration, MFA flows, and CSRF token.
 *
 * @package Modules\User\Controllers
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
	 * Constructor.
	 *
	 * @param string|null $modelClass
	 */
	public function __construct(protected ?string $modelClass = User::class)
	{
		parent::__construct();
	}

	/**
	 * Handle a login request.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function login(Request $req): object
	{
		/**
		 * This will wait for 1 second to prevent brute force attacks.
		 * This is not a security measure, but it will slow down the attacker.
		 * This will also help in reducing the load on the server during multiple attempts.
		 */
		sleep(1);

		$username = $req::input('username');
		$password = $req::input('password');
		if (! $username || ! $password)
		{
			return $this->error('The username and password are required.');
		}

		$attempts = $this->getAttempts($username);
		if ($attempts >= self::MAX_ATTEMPTS)
		{
			return $this->error('Maximum login attempts reached. Please try again later.');
		}

		$userId = $this->authenticate($username, $password);
		if ($userId < 0)
		{
			return $this->error('Invalid credentials. Attempt ' . ++$attempts . ' of ' . self::MAX_ATTEMPTS);
		}

		$user = $this->getUserId($userId);
		if (!$user)
		{
			return $this->error('The user account is not found.');
		}

		if ($user->multiFactor === true)
		{
			$device = $req::json('device');
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
		$service = new MultiFactorAuthService();
		$service->setResources($user, $device);

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
		$service = new MultiFactorAuthService();
		$user = $service->getUser();
		if (!$user)
		{
			return $this->error('The user not found in MFA session.');
		}

		$type = $req::input('type', 'sms');
		$service->sendCode($user, $type);

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
		/**
		 * This will wait for 1 second to prevent brute force attacks.
		 * This is not a security measure, but it will slow down the attacker.
		 * This will also help in reducing the load on the server during multiple attempts.
		 */
		sleep(1);

		$service = new MultiFactorAuthService();
		$user = $service->getUser();
		if (!$user)
		{
			return $this->error('The user not found in MFA session.');
		}

		$device = $service->getDevice();
		if (!$device)
		{
			return $this->error('The device not found in MFA session.');
		}

		$code = $req::input('code');
		$isValid = $service->validateCode($code);
		if ($isValid === false)
		{
			return $this->error('Invalid authentication code.');
		}

		if ($isValid === null)
		{
			return $this->error('Invalid authentication code. Too many attempts.');
		}

		$service->addNewConnection($user, $device, Request::ip());

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
			return $this->error('The user is not authenticated.');
		}

		$user = $this->modelClass::get($userId);
		if (!$user)
		{
			return $this->error('The user is not found.');
		}

		$this->updateStatus($user->id, UserStatus::OFFLINE->value);
		session()->destroy();

		return $this->response(['message' => 'The user has been logged out successfully.']);
	}

	/**
	 * Register a new user.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function register(Request $req): object
	{
		$data = $req::json('user');
		if (!$data)
		{
			return $this->error('The data is invalid for registration.');
		}

		$model = new $this->modelClass($data);
		$result = $model->add();
		if (!$result)
		{
			return $this->error('The registration has failed.');
		}

		$user = $this->modelClass::get($model->id);
		if (!$user)
		{
			return $this->error('The user is not found after registration');
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
	 * @return int
	 */
	protected function authenticate(string $username, string $password): int
	{
		$userId = $this->modelClass::authenticate($username, $password);
		if ($userId < 0)
		{
			LoginAttemptController::create((object)[
				'ipAddress' => Request::ip(),
				'username' => $username
			]);
		}

		return $userId;
	}

	/**
	 * This will udpate the user statue.
	 *
	 * @param User $user
	 * @param string $status
	 * @param string $appId
	 * @return void
	 */
	public function updateStatus(User $user, string $status): void
	{
		$user->status = $status;
		$user->updateStatus();

		$this->updateLoginStatus($user->id, $status);
	}

	/**
	 * Update login status (login/logout) in LoginLog.
	 *
	 * @param int $userId
	 * @param string $status
	 * @return void
	 */
	protected function updateLoginStatus(int $userId, string $status): void
	{
		if ($status === UserStatus::ONLINE->value || $status === UserStatus::OFFLINE->value)
		{
			$direction = $status === UserStatus::ONLINE->value ? 'login' : 'logout';
			LoginLog::create((object)[
				'dateTimeSetup' => date('Y-m-d H:i:s'),
				'userId' => $userId,
				'direction' => $direction
			]);
		}
	}

	/**
	 * Count recent failed login attempts.
	 *
	 * @param string $username
	 * @return int
	 */
	protected function getAttempts(string $username): int
	{
		return LoginAttemptController::countAttempts(Request::ip(), $username);
	}

	/**
	 * Retrieve a user by ID or null.
	 *
	 * @param mixed $userId
	 * @return User|null
	 */
	protected function getUserId(mixed $userId): ?User
	{
		return $this->modelClass::get($userId);
	}

	/**
	 * Validate the password request.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function validatePasswordRequest(Request $req): object
	{
		// This will wait for 1 second to prevent brute force attacks.
		sleep(1);

		$requestId = $req::input('requestId');
		$userId = $req::getInt('userId');
		if (!isset($requestId) || !isset($userId))
		{
			return $this->error('The request id or user id is missing.');
		}

		$service = new PasswordService();
		$username = $service->validateRequest($requestId, $userId);
		if ($username === null)
		{
			return $this->error('No request is found.');
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
		// This will wait for 1 second to prevent brute force attacks.
		sleep(1);

		$user = $req::json('user');
		if (!isset($user))
		{
			return $this->error('The user is not set.');
		}

		if (empty($user->password))
		{
			return $this->error('The password is not set.');
		}

		$requestId = $user->requestId;
		$userId = $user->userId;

		$service = new PasswordService();
		$result = $service->resetPassword($requestId, $userId, $user->password);
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
