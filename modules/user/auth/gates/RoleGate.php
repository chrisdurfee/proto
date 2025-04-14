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
	 * Checks if the user has the specified role.
	 *
	 * @param string $role The role to check.
	 * @return bool True if the user has the role, otherwise false.
	 */
	public function hasRole(string $role): bool
	{
		$user = $this->get('user');
		if (!$user)
		{
			return false;
		}

		$roles = $user->roles ?? [];
		foreach ($roles as $r)
		{
			if ($r->slug === $role)
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
	public function hasRoleName(string $role): bool
	{
		$user = $this->get('user');
		if (!$user)
		{
			return false;
		}

		$roles = $user->roles ?? [];
		foreach ($roles as $r)
		{
			if ($r->name === $role)
			{
				return true;
			}
		}
		return false;
	}
}