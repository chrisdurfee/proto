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
	/**
	 * Checks if the user has the specified permission.
	 *
	 * @param string $permission The permission to check.
	 * @return bool True if the user has the permission, otherwise false.
	 */
	public function hasPermission(string $permission): bool
	{
		$userPermissions = $this->get('user')->permissions ?? [];
		return in_array($permission, $userPermissions, true);
	}
}