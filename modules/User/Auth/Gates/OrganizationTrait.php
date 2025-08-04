<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

/**
 * OrganizationTrait
 *
 * This will add organization-based access control to a gate.
 *
 * @package Modules\User\Auth\Gates
 */
trait OrganizationTrait
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
}