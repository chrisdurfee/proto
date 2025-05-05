<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Auth\Policies\Policy as BasePolicy;
use Modules\User\Auth\Gates\RoleGate;
use Modules\User\Auth\Gates\PermissionGate;
use Modules\User\Auth\Gates\ResourceGate;
use Proto\Controllers\Controller;
use Proto\Http\Router\Request;

/**
 * Policy
 *
 * This policy handles access control for content-related actions.
 *
 * @package Modules\User\Auth\Policies
 */
class Policy extends BasePolicy
{
	/**
	 * A quick cache to avoid repeated checks.
	 *
	 * @var ?bool
	 */
	protected ?bool $isAdmin = null;

	/**
	 * Constructor for the Policy class.
	 *
	 * @param ?Controller $controller The controller instance associated with this policy.
	 * @param ?RoleGate $roleGate The role gate instance for role-based access control.
	 * @param ?PermissionGate $permissionGate The permission gate instance for permission-based access control.
	 * @param ?ResourceGate $resourceGate The resource gate instance for resource-based access control.
	 */
	public function __construct(
		?Controller $controller = null,
		protected ?RoleGate $roleGate = new RoleGate(),
		protected ?PermissionGate $permissionGate = new PermissionGate(),
		protected ?ResourceGate $resourceGate = new ResourceGate()
	)
	{
		parent::__construct($controller);
	}

	/**
	 * Check if user is an admin.
	 *
	 * @return bool True if the user is an admin, otherwise false.
	 */
	protected function isAdmin(): bool
	{
		if ($this->isAdmin !== null)
		{
			return $this->isAdmin;
		}

		return $this->isAdmin = $this->roleGate->hasRole('admin');
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
		return $this->permissionGate->hasPermission($permissionSlug);
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

		return $this->resourceGate->isOwner($ownerId);
	}

	/**
	 * This will get the resource ID from the request.
	 *
	 * @param Request $request
	 * @return int|null
	 */
	protected function getResourceId(Request $request): ?int
	{
		return $request->getInt('id') ?? $request->params()->id ?? null;
	}
}
