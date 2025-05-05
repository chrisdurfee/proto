<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class RolePolicy
 *
 * Policy that governs access control for managing roles.
 *
 * @package Modules\User\Auth\Policies
 */
class RolePolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can list all roles.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function all(Request $request): bool
	{
		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can get a single role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function get(Request $request): bool
	{
		$id = $request->input('id') ?? null;
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can create a new role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can create roles, otherwise false.
	 */
	public function add(Request $request): bool
	{
		return $this->canAccess('roles.create');
	}

	/**
	 * Determines if the user can update an existing role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can edit roles, otherwise false.
	 */
	public function update(Request $request): bool
	{
		return $this->canAccess('roles.edit');
	}

	/**
	 * Determines if the user can delete a role.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can delete roles, otherwise false.
	 */
	public function delete(Request $request): bool
	{
		return $this->canAccess('roles.delete');
	}

	/**
	 * Determines if the user can search roles.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function search(Request $request): bool
	{
		return $this->canAccess('roles.view');
	}
}