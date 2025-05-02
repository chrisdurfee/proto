<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class UserPolicy
 *
 * Policy that governs access control for managing users.
 *
 * @package Modules\User\Auth\Policies
 */
class UserPolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can list all users.
	 *
	 * @param mixed $filter Filter criteria (optional).
	 * @param int|null $offset Pagination offset (optional).
	 * @param int|null $count Number of items to return (optional).
	 * @param array|null $modifiers Additional query modifiers (optional).
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function all(
		mixed $filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): bool {
		return $this->canAccess('users.view');
	}

	/**
	 * Determines if the user can get a single user's information.
	 *
	 * @param mixed $id The user ID.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function get(mixed $id): bool
	{
		return $this->canAccess('users.view') || $this->ownsResource($id);
	}

	/**
	 * Determines if the user can add/create a user.
	 *
	 * @param object $data User data.
	 * @return bool True if the user can create users, otherwise false.
	 */
	public function add(object $data): bool
	{
		return $this->canAccess('users.create');
	}

	/**
	 * Determines if the user can edit an existing user.
	 *
	 * @param mixed $data User data or ID.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	protected function canEdit(mixed $data): bool
	{
		if ($this->canAccess('users.edit'))
		{
			return true;
		}

		$userId = $data->id ?? null;
		if ($userId === null)
		{
			return false;
		}

		return $this->ownsResource($userId);
	}

	/**
	 * Determines if the user can update an existing user.
	 *
	 * @param object $data The updated user data.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	public function update(object $data): bool
	{
		return $this->canEdit($data);
	}

	/**
	 * Determines if the user can update an existing user.
	 *
	 * @param object $data The updated user data.
	 * @return bool True if the user can edit users, otherwise false.
	 */
	public function updateStatus(object $data): bool
	{
		return $this->canEdit($data);
	}

	/**
	 * Determines if the user can verify their email address.
	 *
	 * @param Request $request The request containing the user ID.
	 * @return bool True if the user can verify their email, otherwise false.
	 */
	public function verifyEmail(Request $request): bool
	{
		$userId = $request->input('userId');
		return $this->ownsResource($userId);
	}

	/**
	 * Determines if the user can delete a user.
	 *
	 * @param mixed $data User data or ID.
	 * @return bool True if the user can delete users, otherwise false.
	 */
	public function delete(mixed $data): bool
	{
		return $this->canAccess('users.delete');
	}

	/**
	 * Determines if the user can search among users.
	 *
	 * @param mixed $search The search criteria.
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function search(mixed $search): bool
	{
		return $this->canAccess('users.view');
	}
}