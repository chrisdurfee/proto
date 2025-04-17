<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Modules\User\Models\LoginLog;
use Modules\User\Controllers\LoginAttemptController;
use Proto\Controllers\Controller;
use Proto\Http\Request;
use Proto\Auth\Gates\CrossSiteRequestForgeryGate;

/**
 * AuthController
 *
 * Handles user authentication, registration, and multi-factor flows.
 */
class AuthController extends Controller
{
	/**
	 * Maximum login attempts allowed.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 10;

	/**
	 * Constructor.
	 *
	 * @param string|null $modelClass
	 * @return void
	 */
	public function __construct(
		protected ?string $modelClass = User::class
	)
	{
		parent::__construct();
	}

	/**
	 * This will count login attempt from the ip address.
	 *
	 * @param string $username
	 * @return int
	 */
	protected function getAttempts(string $username): int
	{
		$controller = new LoginAttemptController();
		return $controller->countAttempts(Request::ip(), $username);
	}

	/**
	 * This will check if the attempts are under the max.
	 *
	 * @param int $attempts
	 * @return bool
	 */
	protected function isUnderAttemptMax(int $attempts): bool
	{
		return ($attempts < self::MAX_ATTEMPTS);
	}

	/**
	 * This will handle user login.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function login(Request $req): object
	{
		$username = $req::input('username');
		$password = $req::input('password');
		if (!$username || !$password)
		{
			return $this->error('Username and password are required');
		}

		$attempts = $this->getAttempts($username);
		if ($this->isUnderAttemptMax($attempts) === false)
		{
			return $this->error('You have reached the 15 minute limit for that username.');
		}

		$userId = $this->authenticate($username, $password);
		if ($userId === -1)
		{
			return $this->error('The credentials are invalid. Attempt ' . ++$attempts . ' of ' . self::MAX_ATTEMPTS . '.');
		}

		$user = $this->getUserId($userId);
		if (!$user)
		{
			return $this->error('The user account not found.');
		}

		return (object)
		[
			'user' => $user,
			'success' => true
		];
	}

	/**
	 * This will get the user id and set it to the session.
	 *
	 * @param mixed $userId
	 * @return User|null
	 */
	protected function getUserId(mixed $userId): ?User
	{
		$user = $this->modelClass::get($userId);
		if (! $user)
		{
			return null;
		}

		$this->setSessionUser($user);
		return $user;
	}

	/**
	 * This will set the session user.
	 *
	 * @param User $user
	 * @return void
	 */
	protected function setSessionUser(User $user): void
	{
		setSession('user', $user->getData());
	}

	/**
	 * This will authenticate the user.
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	protected function authenticate(string $username, string $password): int
	{
		$userId = $this->modelClass::authenticate($username, $password);
		if ($userId === -1)
		{
			$this->logAttempt($username);
		}
		return $userId;
	}

	/**
	 * This will log the login attempt.
	 *
	 * @param string $username
	 * @return object
	 */
	protected function logAttempt(string $username): object
	{
		return LoginAttemptController::create((object)[
			'ipAddress' => Request::ip(),
			'username' => $username
		]);
	}

	/**
	 * This will update the user login status to the table.
	 *
	 * @param int $id
	 * @param string $status
	 * @return bool|null
	 */
	protected function updateLoginStatus(int $id, string $status): ?bool
	{
		if ($status === 'online' || $status === 'offline')
		{
			$direction = ($status === 'online')? 'login' : 'logout';
			return LoginLog::create((object)[
				'dateTimeSetup' => date('Y-m-d H:i:s'),
				'userId' => $id,
				'direction' => $direction
			]);
		}
	}

	/**
	 * User logout.
	 *
	 * @return object
	 */
	public function logout(): object
	{
		session()->destroy();

		return $this->response([
			'message' => 'Logged out successfully'
		]);
	}

	/**
	 * User registration.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function register(Request $req): object
	{
		$data = $req::json('user');
		if (!$data)
		{
			return $this->error('Invalid data');
		}

		$model = new $this->modelClass($data);
		$result = $model->add();
		if (! $result)
		{
			return $this->error('Registration failed');
		}

		$user = $this->getUserId($model->id);
		if (! $user)
		{
			return $this->error('User not found');
		}

		return $this->response([
			'user' => $user
		]);
	}

	/**
	 * Generate a simple multi-factor auth code and store it in session.
	 *
	 * @return object
	 */
	public function getAuthCode(): object
	{
		$userId = getSession('userId');
		if (! $userId)
		{
			return $this->error('Not authenticated');
		}

		// Generate a 6-digit code
		$code = random_int(100000, 999999);
		setSession('authCode', $code);

		return (object)
		[
			'code' => $code,
			'success' => true
		];
	}

	/**
	 * Verify the multi-factor authentication code.
	 *
	 * @param Request $req
	 * @return object
	 */
	public function verifyAuthCode(Request $req): object
	{
		$entered = $req->input('code');
		$code = getSession('authCode');
		if (! $entered || (int) $entered !== (int) $code)
		{
			return $this->error('Invalid authentication code');
		}

		// Clear stored code
		unsetSession('authCode');

		return (object)
		[
			'success' => true
		];

	}

	/**
	 * Retrieve a fresh CSRF token.
	 *
	 * @return object
	 */
	public function getToken(): object
	{
		$gate = new CrossSiteRequestForgeryGate();
		$token = $gate->setToken();

		return (object)
		[
			'token' => $token
		];
	}
}
