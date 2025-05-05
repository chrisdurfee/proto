<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class PermissionPolicy
 *
 * Policy that governs access control for viewing or assigning permissions.
 *
 * @package Modules\User\Auth\Policies
 */
class PermissionPolicy extends Policy
{
	/**
	 * Default policy for methods without an explicit handler.
	 *
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('permissions.view');
	}

	/**
	 * Determines if the user can list all permissions.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function all(Request $request): bool
	{
		return $this->canAccess('permissions.view');
	}

	/**
	 * Determines if the user can get a single permission resource.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function get(Request $request): bool
	{
		$id = $request->input('id') ?? null;
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('permissions.view');
	}

	/**
	 * Determines if the user can assign or create new permissions.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can assign permissions, otherwise false.
	 */
	public function add(Request $request): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can update existing permissions.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can assign permissions, otherwise false.
	 */
	public function update(Request $request): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can delete a permission.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can delete permissions, otherwise false.
	 */
	public function delete(Request $request): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can search permissions.
	 *
	 * @param Request $request The request object.
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function search(Request $request): bool
	{
		return $this->canAccess('permissions.view');
	}
}
