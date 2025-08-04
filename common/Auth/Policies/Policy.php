<?php declare(strict_types=1);
namespace Common\Auth\Policies;

use Proto\Auth\Policies\Policy as BasePolicy;

/**
 * Policy
 *
 * This policy handles access control for content-related actions.
 *
 * @package Common\Auth\Policies
 */
class Policy extends BasePolicy
{
    /**
	 * Check if user is an admin.
	 *
	 * @return bool True if the user is an admin, otherwise false.
	 */
	protected function isAdmin(): bool
	{
		return auth()->role->hasRole('admin');
	}

    /**
	 * Helper method to check either "admin" role
	 * or a specific permission slug.
	 *
	 * @param string $permissionSlug The permission slug to check.
	 * @return bool True if the user has the permission, otherwise false.
	 */
	protected function canAccess(string $permissionSlug): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}
		return auth()->permission->hasPermission($permissionSlug);
	}

	/**
	 * Helper method to check if the user owns a resource.
	 *
	 * @param mixed $ownerId The resource or owner value.
	 * @return bool True if the user owns the resource, otherwise false.
	 */
	protected function ownsResource(mixed $ownerId): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return auth()->resource->isOwner($ownerId);
	}
}