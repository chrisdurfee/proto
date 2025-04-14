<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

/**
 * AdminResourcePolicy
 *
 * This will create a policy for the admin resource.
 *
 * @package Modules\User\Auth\Policies
 */
class AdminResourcePolicy extends Policy
{
	/**
	 * This will secure all non standard methods.
	 *
	 * @return bool
	 */
	public function default(): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if a user can get a resource.
	 *
	 * @param mixed $id
	 * @return bool
	 */
	public function get(mixed $id): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update a resource.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function setup(object $data): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can add a resource.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function add(object $data): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update a resource.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function update(object $data): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update the status of a resource.
	 *
	 * @param mixed $id
	 * @param mixed $status
	 * @return bool
	 */
	public function updateStatus(mixed $id, $status): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can delete a resource.
	 *
	 * @param int|object $data
	 * @return bool
	 */
	public function delete(int|object $data): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if a user can get a resource.
	 *
	 * @param array|null $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return bool
	 */
	public function all(
		mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null
	): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if a user can search a resource.
	 *
	 * @param mixed $search
	 * @return bool
	 */
	public function search(mixed $search): bool
	{
		return $this->isAdmin();
	}
}