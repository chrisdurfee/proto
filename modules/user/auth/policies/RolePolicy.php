<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

/**
 * Class RolePolicy
 *
 * Policy that governs access control for managing roles.
 *
 * @package Modules\User\Auth\Policies
 */
class RolePolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can list all roles.
	 *
	 * @param mixed $filter Filter criteria (optional).
	 * @param int|null $offset Pagination offset (optional).
	 * @param int|null $count Number of items to return (optional).
	 * @param array|null $modifiers Additional query modifiers (optional).
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function all(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): bool {
		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can get a single role.
	 *
	 * @param mixed $id The role ID.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function get(mixed $id): bool
	{
		return $this->canAccess('roles.view');
	}

	/**
	 * Determines if the user can create a new role.
	 *
	 * @param object $data Role data.
	 * @return bool True if the user can create roles, otherwise false.
	 */
	public function add(object $data): bool
	{
		return $this->canAccess('roles.create');
	}

	/**
	 * Determines if the user can update an existing role.
	 *
	 * @param object $data Updated role data.
	 * @return bool True if the user can edit roles, otherwise false.
	 */
	public function update(object $data): bool
	{
		return $this->canAccess('roles.edit');
	}

	/**
	 * Determines if the user can delete a role.
	 *
	 * @param mixed $data Role data or ID.
	 * @return bool True if the user can delete roles, otherwise false.
	 */
	public function delete(mixed $data): bool
	{
		return $this->canAccess('roles.delete');
	}

	/**
	 * Determines if the user can search roles.
	 *
	 * @param mixed $search Search criteria.
	 * @return bool True if the user can view roles, otherwise false.
	 */
	public function search(mixed $search): bool
	{
		return $this->canAccess('roles.view');
	}
}