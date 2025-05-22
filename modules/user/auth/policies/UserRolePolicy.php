<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class UserRolePolicy
 *
 * Policy that governs access control for managing user roles.
 *
 * @package Modules\User\Auth\Policies
 */
class UserRolePolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can list all user roles.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function all(Request $request): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can get a single user roles.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function get(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can create a new role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can create roles, otherwise false.
	 */
	public function add(Request $request): bool
	{
		return $this->canAccess('users.edit');
	}

	/**
	 * Determines if the user can update an existing role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can edit roles, otherwise false.
	 */
	public function update(Request $request): bool
	{
		return $this->canAccess('users.edit');
	}

	/**
	 * Determines if the user can delete a role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can delete roles, otherwise false.
	 */
	public function delete(Request $request): bool
	{
		return $this->canAccess('users.edit');
	}

	/**
	 * Determines if the user can search user roles.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function search(Request $request): bool
	{
		return $this->canAccess('users.view');
	}
}