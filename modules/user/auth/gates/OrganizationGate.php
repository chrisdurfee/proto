<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * OrganizationGate
 *
 * This will create a organization-based access control gate.
 *
 * @package Modules\User\Auth\Gates
 */
class OrganizationGate extends Gate
{
	/**
	 * Helper method to check if the current user can access a specific organization.
	 *
	 * @param mixed $orgId The organization ID to check against.
	 * @return bool True if the current user has access to the organization, otherwise false.
	 */
	public function canAccess(mixed $orgId): bool
	{
		$currentUser = $this->get('user');
		if (!isset($currentUser->id))
		{
			return false;
		}

		$organizations = $currentUser->organizations ?? [];
		foreach ($organizations as $organization)
		{
			if ($organization->id === $orgId)
			{
				return true;
			}
		}
		return false;
	}
}