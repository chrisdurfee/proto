<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Modules\User\Models\LoginLog;
use Modules\User\Models\LoginAttempt;
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
	 * User login.
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

		$userId = $this->authenticate($username, $password);
		if ($userId === -1)
		{
			return $this->error('Invalid email or password');
		}

		$user = $this->modelClass::get($userId);
		if (! $user)
		{
			return $this->error('User not found');
		}

		$this->setSessionUser($user);

		return (object)
		[
			'user' => $user,
			'success' => true
		];
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
		/* we want to check if the user has loged in or out */
		if ($status === 'online' || $status === 'offline')
		{
			/* we want to log the login or logout */
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

		$user = $this->modelClass::get($model->id);
		if (! $user)
		{
			return $this->error('User not found');
		}

		$this->setSessionUser($user);

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
