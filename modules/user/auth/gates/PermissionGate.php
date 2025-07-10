<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * PermissionGate
 *
 * This will create a permission-based access control gate.
 *
 * @package Modules\User\Auth\Gates
 */
class PermissionGate extends Gate
{
	use OrganizationTrait;

	/**
	 * Checks if the user has the specified permission.
	 *
	 * @param string $permission The permission to check.
	 * @param int|null $organizationId The organization ID to check against.
	 * @return bool True if the user has the permission, otherwise false.
	 */
	public function hasPermission(string $permission, ?int $organizationId = null): bool
	{
		$user = $this->get('user');
		if ($user === null)
		{
			return false;
		}

		$roles = $user->roles ?? [];
		foreach ($roles as $role)
		{
			if (!$this->canAccessOrg($organizationId, (object)$role))
			{
				continue;
			}

			$permissions = $role->permissions ?? [];
			foreach ($permissions as $perm)
			{
				if ($perm->slug === $permission)
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks if the user has the specified permission.
	 *
	 * @param string $permission The permission name to check.
	 * @param int|null $organizationId The organization ID to check against.
	 * @return bool True if the user has the permission, otherwise false.
	 */
	public static function has(string $permission, ?int $organizationId = null): bool
	{
		$instance = new self();
		return $instance->hasPermission($permission, $organizationId);
	}
}