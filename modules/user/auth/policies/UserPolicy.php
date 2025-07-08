<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class UserPolicy
 *
 * Policy that governs access control for managing users.
 *
 * @package Modules\User\Auth\Policies
 */
class UserPolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can list all users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function all(Request $request): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can get a single user's information.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function get(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('users.view') || $this->ownsResource($id);
	}

	/**
	 * Determines if the user can add/create a user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can create users, otherwise false.
	 */
	public function add(Request $request): bool
	{
		return $this->canAccess('users.create');
	}

	/**
	 * Determines if the user can edit an existing user.
	 *
	 * @param mixed $data User data or ID.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	protected function canEdit(mixed $data): bool
	{
		if ($this->canAccess('users.edit'))
		{
			return true;
		}

		$userId = $data->id ?? null;
		if ($userId === null)
		{
			return false;
		}

		return $this->ownsResource($userId);
	}

	/**
	 * Determines if the user can update an existing user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	public function update(Request $request): bool
	{
		$data = $this->controller->getRequestItem($request);
		$data->id = $data->id ?? $this->getResourceId($request);
		if (empty($data) || empty($data->id))
		{
			return false;
		}

		return $this->canEdit($data);
	}

	/**
	 * Determines if the user can update an existing user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	public function updateStatus(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canEdit((object) ['id' => $id]);
	}

	/**
	 * Determines if the user can verify their email address.
	 *
	 * @param Request $request The request containing the user ID.
	 * @return bool True if the user can verify their email, otherwise false.
	 */
	public function verifyEmail(Request $request): bool
	{
		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return false;
		}

		return $this->ownsResource($userId);
	}

	/**
	 * Determines if the user can unsubscribe.
	 *
	 * @param Request $request The request containing the user ID.
	 * @return bool True if the user can unsubscribe, otherwise false.
	 */
	public function unsubscribe(Request $request): bool
	{
		return true;
	}

	/**
	 * Determines if the user can update their credentials.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function updateCredentials(Request $request): bool
	{
		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return false;
		}

		return $this->canEdit((object) ['id' => $userId]);
	}

	/**
	 * Determines if the user can get roles for a user.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function getRoles(Request $request): bool
	{
		$id = $request->params()->userId ?? null;
		if ($id === null)
		{
			return false;
		}

		return $this->canEdit((object) ['id' => $id]);
	}

	/**
	 * Determines if the user can delete a user.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can delete users, otherwise false.
	 */
	public function delete(Request $request): bool
	{
		return $this->canAccess('users.delete');
	}

	/**
	 * Determines if the user can search among users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function search(Request $request): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can count among users.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can count users, otherwise false.
	 */
	public function count(Request $request): bool
	{
		return $this->canAccess('users.view');
	}
}