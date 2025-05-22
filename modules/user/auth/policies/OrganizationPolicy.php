<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * OrganizationPolicy
 *
 * Governs access to organization resources.
 */
class OrganizationPolicy extends Policy
{
	/**
	 * Default fallback for methods without an explicit policy.
	 *
	 * @return bool
	 */
	public function default(): bool
	{
		return $this->canAccess('organization.view');
	}

	/**
	 * Determine if the user can list all organizations.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function all(Request $request): bool
	{
		return $this->canAccess('organization.view');
	}

	/**
	 * Determine if the user can view a single organization.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function get(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('organization.view');
	}

	/**
	 * Determine if the user can create a new organization.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function add(Request $request): bool
	{
		return $this->canAccess('organization.create');
	}

	/**
	 * Shared logic for editing an organization.
	 *
	 * @param mixed $data
	 * @return bool
	 */
	protected function canEdit(mixed $data): bool
	{
		return $this->canAccess('organization.edit');
	}

	/**
	 * Determine if the user can update an existing organization.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function update(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canEdit((object) ['id' => $id]);
	}

	/**
	 * Determine if the user can delete an organization.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function delete(Request $request): bool
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return false;
		}

		return $this->canAccess('organization.delete');
	}

	/**
	 * Determine if the user can search organizations.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function search(Request $request): bool
	{
		return $this->canAccess('organization.view');
	}

	/**
	 * Determine if the user can count organizations.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function count(Request $request): bool
	{
		return $this->canAccess('organization.view');
	}
}