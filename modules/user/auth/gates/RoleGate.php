<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * RoleGate
 *
 * This will create a role-based access control gate.
 *
 * @package Modules\User\Auth\Gates
 */
class RoleGate extends Gate
{
	use OrganizationTrait;

	/**
	 * Checks if the user is an admin.
	 *
	 * @return bool True if the user is an admin, otherwise false.
	 */
	public function isAdmin(): bool
	{
		$user = $this->get('user');
		if (!$user)
		{
			return false;
		}

		return $this->hasRole('admin');
	}

	/**
	 * Checks if the user has the specified role.
	 *
	 * @param string $roleSlug The role to check.
	 * @param int|null $organizationId The organization ID to check against.
	 * @return bool True if the user has the role, otherwise false.
	 */
	public function hasRole(string $roleSlug, ?int $organizationId = null): bool
	{
		$user = $this->get('user');
		if (!$user)
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

			if ($role->slug === $roleSlug)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if the user has the specified role.
	 *
	 * @param string $roleName The role name to check.
	 * @param int|null $organizationId The organization ID to check against.
	 * @return bool True if the user has the role, otherwise false.
	 */
	public function hasRoleName(string $roleName, ?int $organizationId = null): bool
	{
		$user = $this->get('user');
		if (!$user)
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

			if ($role->name === $roleName)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if the user has the specified role.
	 *
	 * @param string $role The role name to check.
	 * @return bool True if the user has the role, otherwise false.
	 */
	public static function has(string $role): bool
	{
		$instance = new self();
		return $instance->hasRole($role);
	}
}