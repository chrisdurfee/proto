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
	/**
	 * This will check if the user has access to the organization.
	 *
	 * @param mixed $organizationId
	 * @param object $role
	 * @return bool
	 */
	protected function canAccessOrg(?int $organizationId, object $role): bool
	{
		return !isset($organizationId) || ($role->organizationId === $organizationId);
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
			if (!$this->canAccessOrg($organizationId, $role))
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
			if (!$this->canAccessOrg($organizationId, $role))
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