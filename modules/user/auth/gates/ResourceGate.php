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
class ResourceGate extends Gate
{
	/**
	 * Helper method to check if the current user owns a resource.
	 *
	 * @param mixed $ownerId The resource or owner value.
	 * @return bool True if the current user owns the resource, otherwise false.
	 */
	public function ownsResource(mixed $ownerId): bool
	{
		$currentUser = $this->get('user');
		if (!isset($currentUser->id))
		{
			return false;
		}

		$currentUserId = $currentUser->id;
		return $ownerId === $currentUserId;
	}

	/**
	 * Helper method to check if the current user owns a resource.
	 *
	 * @param mixed $ownerId The resource or owner value.
	 * @return bool True if the current user owns the resource, otherwise false.
	 */
	public static function isOwner(mixed $ownerId): bool
	{
		$instance = new self();
		return $instance->ownsResource($ownerId);
	}
}