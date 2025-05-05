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
	 * @return object
	 */
	public function verifyEmail(Request $request): object
	{
		$userId = $request->get('userId');
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		$gate = new EmailVerificationGate();
		if (!$gate->isValid($request->get('requestId'), $userId))
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
}
