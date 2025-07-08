<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Gates\EmailVerificationGate;
use Modules\User\Models\User;
use Modules\User\Auth\Policies\UserPolicy;
use Modules\User\Services\User\PasswordUpdateService;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Modules\User\Models\NotificationPreference;

/*
 * UserController
 *
 * This is the controller class for the model "User".
 *
 * @package Modules\User\Controllers
 */
class UserController extends ResourceController
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = UserPolicy::class;

	/**
	 * This will setup the model class.
	 *
	 * @param string|null $model by using the magic constant ::class
	 */
	public function __construct(
		protected ?string $model = User::class
	)
	{
		parent::__construct();
	}

	/**
	 * This will return the validation rules for the model.
	 *
	 * @return array<string, string>
	 */
	protected function validate(): array
	{
		return [
			'firstName' => 'string:255|required',
			'lastName' => 'string:255|required',
			'email' => 'email:255|required'
		];
	}

	/**
	 * Adds a model entry.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function add(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data) || empty($data->username))
		{
			return $this->error('No item provided.');
		}

		$isTaken = User::isUsernameTaken($data->username ?? '');
		if ($isTaken)
		{
			return $this->error('Username is already taken.');
		}

		return parent::add($request);
	}

	/**
	 * This will verify the email address.
	 *
	 * @param Request $request
	 * @param EmailVerificationGate $gate
	 * @return object
	 */
	public function verifyEmail(
		Request $request,
		EmailVerificationGate $gate = new EmailVerificationGate()
	): object
	{
		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		if (!$gate->isValid($request->input('token'), $userId))
		{
			return $this->error('Invalid request.');
		}

		/**
		 * This will udate the request status.
		 */
		$gate->updateRequest();

		/**
		 * This will add the email verified date to the user.
		 */
		return parent::update((object)[
			'id' => $userId,
			'emailVerifiedAt' => date('Y-m-d H:i:s')
		]);
	}

	/**
	 * This will unsubscribe the user.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function unsubscribe(
		Request $request
	): object
	{
		$userId = $this->getResourceId($request);
		$data = (object)[
			'userId' => $userId,
		];

		$allowEmail = $request->getInt('allowEmail') ?? 0;
		if (isset($allowEmail))
		{
			$data->allowEmail = $allowEmail;
		}

		$allowSms = $request->getInt('allowSms');
		if (isset($allowSms))
		{
			$data->allowSms = $allowSms;
		}

		$allowPush = $request->getInt('allowPush');
		if (isset($allowPush))
		{
			$data->allowPush = $allowPush;
		}

		/**
		 * This will add the email verified date to the user.
		 */
		$result = NotificationPreference::put((object)[
			'userId' => $userId,
		]);

		return (!$result)? $this->error('Failed to unsubscribe user.') : $this->response([
			'message' => 'User unsubscribed successfully.'
		]);
	}

	/**
	 * Updates the user credentials.
	 *
	 * @param Request $request The request object.
	 * @param PasswordUpdateService $service The service to handle password updates.
	 * @return object The response.
	 */
	public function updateCredentials(
		Request $request,
		PasswordUpdateService $service = new PasswordUpdateService()
		): object
	{
		$userId = $this->getResourceId($request);
		if (!isset($userId))
		{
			return $this->error('Invalid user ID.');
		}

		$username = $request->input('username');
		if ($username === "undefined")
		{
			$username = null;
		}

		$password = $request->input('password');
		if ($password === "undefined")
		{
			$password = null;
		}

		$data = (object)[
			'id' => $userId,
			'username' => $username,
			'password' => $password
		];

		$result = $service->updateCredentials($data);
		return $this->response($result);
	}

	/**
	 * This will get the user roles.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function getRoles(
		Request $request
	): object
	{
		$userId = $request->params()->userId ?? null;
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		/**
		 * This will get the user roles.
		 */
		$model = $this->model::get($userId);
		if ($model === null)
		{
			return $this->error('User not found.');
		}

		return $this->response([
			'rows' => $model->roles
		]);
	}

	/**
	 * This will get the user roles.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function debugRoles(
		Request $request
	): object
	{
		$userId = $request->params()->userId ?? null;
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		/**
		 * This will get the user roles.
		 */
		$model = $this->model::get($userId);
		if ($model === null)
		{
			return $this->error('User not found.');
		}

		$roles = $model->roles();
		echo '<pre>';
		//var_dump($roles->attach(19));
		//var_dump($roles->detach(19));
		//var_dump($roles->sync([19]));
		//var_dump($roles->toggle([19, 18]));

		return $this->response([
			'rows' => $roles->getResults()
		]);
	}
}