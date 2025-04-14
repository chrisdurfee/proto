<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

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
	 * @param mixed $filter Filter criteria (optional).
	 * @param int|null $offset Pagination offset (optional).
	 * @param int|null $count Number of items to return (optional).
	 * @param array|null $modifiers Additional query modifiers (optional).
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function all(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): bool {
		return $this->canAccess('permissions.view');
	}

	/**
	 * Determines if the user can get a single permission resource.
	 *
	 * @param mixed $id The permission ID.
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function get(mixed $id): bool
	{
		return $this->canAccess('permissions.view');
	}

	/**
	 * Determines if the user can assign or create new permissions.
	 *
	 * @param object $data The permission data.
	 * @return bool True if the user can assign permissions, otherwise false.
	 */
	public function add(object $data): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can update existing permissions.
	 *
	 * @param object $data Updated permission data.
	 * @return bool True if the user can assign permissions, otherwise false.
	 */
	public function update(object $data): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can delete a permission.
	 *
	 * @param mixed $data Permission data or ID.
	 * @return bool True if the user can assign permissions, otherwise false.
	 */
	public function delete(mixed $data): bool
	{
		return $this->canAccess('permissions.assign');
	}

	/**
	 * Determines if the user can search permissions.
	 *
	 * @param mixed $search Search criteria.
	 * @return bool True if the user can view permissions, otherwise false.
	 */
	public function search(mixed $search): bool
	{
		return $this->canAccess('permissions.view');
	}
}
