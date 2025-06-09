<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Gates\EmailVerificationGate;
use Modules\User\Models\User;
use Modules\User\Auth\Policies\UserPolicy;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;

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
	 * @param string|null $modelClass by using the magic constant ::class
	 */
	public function __construct(
		protected ?string $modelClass = User::class
	)
	{
		parent::__construct();
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
		$userId = $request->getInt('userId');
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		if (!$gate->isValid($request->input('requestId'), $userId))
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