<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Gates\EmailVerificationGate;
use Modules\User\Models\User;
use Modules\User\Auth\Policies\UserPolicy;
use Modules\User\Services\User\PasswordUpdateService;
use Modules\User\Services\User\UnsubscribeService;
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
			'email' => 'email:255|required',
			'displayName' => 'string:150',
			'image' => 'string:150'
		];
	}

	/**
	 * Updates a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function addItem(object $data): object
	{
		/**
		 * Check if the username is taken.
		 */
		if (User::isUsernameTaken($data->username ?? ''))
		{
			return $this->error('Username is already taken.');
		}

		return parent::addItem($data);
	}

	/**
	 * Restricts the data that can be updated.
	 *
	 * @param object $data The data to restrict.
	 * @return void
	 */
	protected function restrictData(object &$data): void
	{
		$fields = ['username', 'password', 'emailVerifiedAt', 'acceptedTermsAt', 'trialMode', 'trialDaysLeft', 'followerCount', 'deletedAt'];
		foreach ($fields as $field)
		{
			unset($data->$field);
		}
	}

	/**
	 * Updates a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function updateItem(object $data): object
	{
		if (!auth()->permission->hasPermission('user.edit'))
		{
			/**
			 * Restrict admin controls.
			 */
			unset($data->enabled);
		}

		/**
		 * Restrict the username, password, and other sensitive fields from being updated. This
		 * should be done elsewhere to prevent unauthorized changes.
		 */
		$this->restrictData($data);

		return parent::updateItem($data);
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
		 * This will add the email verified date to the user.
		 */
		$response = parent::update((object)[
			'id' => $userId,
			'emailVerifiedAt' => date('Y-m-d H:i:s')
		]);

		if ($response->success)
		{
			/**
			 * This will update the request status.
			 */
			$gate->updateRequest();
		}

		return $response;
	}

	/**
	 * This will unsubscribe the user.
	 *
	 * @param Request $request
	 * @param UnsubscribeService $service
	 * @return object
	 */
	public function unsubscribe(
		Request $request,
		UnsubscribeService $service = new UnsubscribeService()
	): object
	{
		$data = (object)[
			'email' => $request->input('email'),
			'requestId' => $request->input('requestId')
		];

		if (!$data->email || !$data->requestId)
		{
			return $this->error('Invalid unsubscribe request.');
		}

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

		$result = $service->unsubscribe($data);
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