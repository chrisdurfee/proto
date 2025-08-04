<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

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
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function get(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function setup(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can add a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function add(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function update(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can update the status of a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function updateStatus(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if the user can delete a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function delete(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if a user can get a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function all(Request $request): bool
	{
		return $this->isAdmin();
	}

	/**
	 * This will check if a user can search a resource.
	 *
	 * @param Request $request The request object.
	 * @return bool
	 */
	public function search(Request $request): bool
	{
		return $this->isAdmin();
	}
}