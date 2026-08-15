<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

use Proto\Controllers\ControllerInterface;
use Proto\Http\Router\Request;

/**
 * Class Policy
 *
 * Base class for authentication policies.
 *
 * Subclasses define per-action methods (e.g. get, add, update, delete)
 * that receive a Request object and return bool.
 *
 * Features:
 * - Policy method signature validation in development mode
 * - Missing $type property detection for concrete policies
 * - Ownership helper methods for common authorization patterns
 *
 * @package Proto\Auth\Policies
 * @abstract
 */
abstract class Policy
{
	/**
	 * The type identifier for this policy.
	 *
	 * Concrete policies should set this to enable type-based dispatch.
	 * If null on a non-abstract concrete policy, the type is auto-inferred
	 * from the class name (e.g., EventPolicy → 'event',
	 * GroupPostPolicy → 'groupPost').
	 *
	 * @var string|null
	 */
	protected ?string $type = null;

	/**
	 * This will create a new instance of the policy.
	 *
	 * @param ?ControllerInterface $controller The controller instance associated with this policy.
	 * @return void
	 */
	public function __construct(protected ?ControllerInterface $controller = null)
	{
		$this->type = $this->resolveType();
		$this->validatePolicy();
	}

	/**
	 * Resolves the policy type.
	 *
	 * If $type is explicitly set, uses that. Otherwise, auto-infers
	 * from the class name by stripping 'Policy' and lowercasing the
	 * first character (e.g., EventPolicy → 'event', GroupPostPolicy → 'groupPost').
	 *
	 * @return string|null
	 */
	protected function resolveType(): ?string
	{
		if ($this->type !== null)
		{
			return $this->type;
		}

		$ref = new \ReflectionClass($this);
		if ($ref->isAbstract())
		{
			return null;
		}

		$class = $ref->getShortName();
		$type = str_replace('Policy', '', $class);
		return lcfirst($type);
	}

	/**
	 * Validates the policy configuration in development mode.
	 *
	 * Checks:
	 * - Concrete policies have a $type property set
	 * - Policy action methods have the correct signature
	 *
	 * @return void
	 */
	protected function validatePolicy(): void
	{
		$isDev = $this->isDevelopment();

		if ($isDev)
		{
			$this->validateTypeProperty();
		}

		$this->validateMethodSignatures($isDev);
	}

	/**
	 * Checks if the application is running in development mode.
	 *
	 * @return bool
	 */
	protected function isDevelopment(): bool
	{
		$env = (function_exists('env'))
			? env('env')
			: ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production');
		return in_array($env, ['development', 'dev', 'local', 'testing'], true);
	}

	/**
	 * Validates that the policy $type follows camelCase convention.
	 *
	 * Auto-inferred types always follow convention. This catches
	 * manually set types that use kebab-case, snake_case, or dot notation.
	 *
	 * @return void
	 */
	protected function validateTypeProperty(): void
	{
		if ($this->type === null)
		{
			return;
		}

		if (preg_match('/[-._]/', $this->type))
		{
			trigger_error(
				'Policy ' . static::class . ' has $type "' . $this->type . '" which uses non-camelCase characters. '
				. 'Policy types should use camelCase convention (e.g., "groupPost" not "group-post").',
				E_USER_NOTICE
			);
		}
	}

	/**
	 * Validates that policy action methods have the correct signature.
	 *
	 * In development: triggers a visible E_USER_WARNING for immediate feedback.
	 * In production: logs mismatches via error_log so typos don't silently fail.
	 *
	 * @param bool $isDev Whether the application is in development mode.
	 * @return void
	 */
	protected function validateMethodSignatures(bool $isDev = false): void
	{
		$actionMethods = ['get', 'add', 'update', 'delete', 'all', 'search', 'count', 'setup', 'merge'];

		foreach ($actionMethods as $method)
		{
			if (!method_exists($this, $method))
			{
				continue;
			}

			$ref = new \ReflectionMethod($this, $method);

			/**
			 * Skip methods declared in the base Policy class itself.
			 */
			if ($ref->getDeclaringClass()->getName() === self::class)
			{
				continue;
			}

			$params = $ref->getParameters();

			/**
			 * Policy methods are called by PolicyProxy::callMethod() which
			 * passes the same arguments as the controller receives — typically
			 * a single Request object. Methods that accept (int $id) or no
			 * parameters will never match and silently fall through to default().
			 */
			if (count($params) === 1)
			{
				$paramType = $params[0]->getType();
				$typeName = ($paramType instanceof \ReflectionNamedType)
					? $paramType->getName()
					: null;

				if ($typeName === Request::class)
				{
					continue;
				}
			}

			/**
			 * Zero-parameter methods are also valid — the PolicyProxy
			 * calls them with arguments but PHP ignores excess arguments.
			 */
			if (count($params) === 0)
			{
				continue;
			}

			$message = "Policy method " . static::class . "::{$method}() has an unexpected signature. "
				. "Expected (Request \$request): bool or no parameters. "
				. "The current signature will cause the method to never be called by the policy dispatcher.";

			if ($isDev)
			{
				trigger_error($message, E_USER_WARNING);
			}
			else
			{
				error_log($message);
			}
		}
	}

	/**
	 * This will get the resource ID from the request.
	 *
	 * @param Request $request
	 * @return int|null
	 */
	protected function getResourceId(Request $request): ?int
	{
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}

	/**
	 * Gets the current session user's ID.
	 *
	 * @return int|null
	 */
	protected function getUserId(): ?int
	{
		$userId = session()->user->id ?? null;
		return ($userId !== null) ? (int) $userId : null;
	}

	/**
	 * Checks if a user is signed in for this request.
	 *
	 * Uses the auth registry `user` gate when available; otherwise falls
	 * back to a non-null session user id.
	 *
	 * @return bool
	 */
	protected function isSignedIn(): bool
	{
		if (function_exists('auth'))
		{
			$userGate = auth()->user ?? null;
			if ($userGate !== null && method_exists($userGate, 'isSignedIn'))
			{
				return (bool) $userGate->isSignedIn();
			}
		}

		return $this->getUserId() !== null;
	}

	/**
	 * Checks if the given user ID matches the current session user.
	 *
	 * This is the simplest ownership check — use for resources that
	 * have a direct userId field. Fail-closed when either id is null.
	 * Apps that grant admins an ownership bypass should override this
	 * method (do not change the default semantics here).
	 *
	 * @param int|null $resourceUserId The userId from the resource.
	 * @return bool
	 */
	protected function ownsResource(?int $resourceUserId): bool
	{
		$userId = $this->getUserId();
		if ($userId === null || $resourceUserId === null)
		{
			return false;
		}

		return $userId === $resourceUserId;
	}

	/**
	 * Policy for resource PUT (upsert) requests.
	 *
	 * PUT and PATCH are semantically equivalent writes, so `setup` must
	 * never be more permissive than `update`. When a subclass defines
	 * `update()`, its ownership rules are reused; otherwise the request
	 * falls back to `default()` when present, else denies.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function setup(Request $request): bool
	{
		if (method_exists($this, 'update'))
		{
			return $this->update($request);
		}

		if (method_exists($this, 'default'))
		{
			return $this->default($request);
		}

		return false;
	}

	/**
	 * Checks if the signed-in user has a role via the auth registry.
	 *
	 * Fails closed when the role gate is unavailable.
	 *
	 * @param string $roleSlug
	 * @return bool
	 */
	protected function hasRole(string $roleSlug): bool
	{
		if (!function_exists('auth'))
		{
			return false;
		}

		$roleGate = auth()->role ?? null;
		if ($roleGate === null || !method_exists($roleGate, 'hasRole'))
		{
			return false;
		}

		return (bool) $roleGate->hasRole($roleSlug);
	}

	/**
	 * Checks if the signed-in user has a permission via the auth registry.
	 *
	 * Fails closed when the permission gate is unavailable. Apps that grant
	 * admins an implicit bypass should override this method.
	 *
	 * @param string $permissionSlug
	 * @return bool
	 */
	protected function hasPermission(string $permissionSlug): bool
	{
		if (!function_exists('auth'))
		{
			return false;
		}

		$permissionGate = auth()->permission ?? null;
		if ($permissionGate === null || !method_exists($permissionGate, 'hasPermission'))
		{
			return false;
		}

		return (bool) $permissionGate->hasPermission($permissionSlug);
	}

	/**
	 * Returns true when the current user has the admin role OR owns the
	 * resource. Fail-closed when `$resourceOwnerId` is null and the user
	 * is not an admin.
	 *
	 * @param int|null $resourceOwnerId
	 * @param string $adminRole Admin role slug (default 'admin').
	 * @return bool
	 */
	protected function isOwnerOrAdmin(?int $resourceOwnerId, string $adminRole = 'admin'): bool
	{
		if ($this->hasRole($adminRole))
		{
			return true;
		}

		if ($resourceOwnerId === null)
		{
			return false;
		}

		return $this->ownsResource($resourceOwnerId);
	}

	/**
	 * Visibility helper: public/published statuses OR owner/admin.
	 *
	 * @param string|null $visibility
	 * @param int|null $resourceOwnerId
	 * @param array<int, string> $publicValues
	 * @param string $adminRole
	 * @return bool
	 */
	protected function isPublicOrOwner(
		?string $visibility,
		?int $resourceOwnerId,
		array $publicValues = ['public', 'published'],
		string $adminRole = 'admin'
	): bool
	{
		if ($visibility !== null && in_array($visibility, $publicValues, true))
		{
			return true;
		}

		return $this->isOwnerOrAdmin($resourceOwnerId, $adminRole);
	}

	/**
	 * Checks if the request's route userId parameter matches the session user.
	 *
	 * Useful for routes like /user/:userId/resource where the userId
	 * in the URL must match the authenticated user.
	 *
	 * @param Request $request The request object.
	 * @param string $paramName The route parameter name (default: 'userId').
	 * @return bool
	 */
	protected function matchesRouteUser(Request $request, string $paramName = 'userId'): bool
	{
		$params = $request->params();
		$routeUserId = (int)($params->$paramName ?? 0);
		if ($routeUserId === 0)
		{
			return false;
		}

		return $this->ownsResource($routeUserId);
	}

	/**
	 * Read a route parameter as int, returning null when missing or zero.
	 *
	 * @param Request $request
	 * @param string $paramName
	 * @return int|null
	 */
	protected function getRouteParam(Request $request, string $paramName): ?int
	{
		$value = $request->params()->$paramName ?? null;
		if ($value === null)
		{
			return null;
		}

		$value = (int) $value;
		return $value > 0 ? $value : null;
	}

	/**
	 * Append authorization list-gates to a filter.
	 *
	 * Override in resource policies for audience / network / owner
	 * restrictions that must run on every all() query. The default
	 * is a no-op so existing policies stay unchanged.
	 *
	 * @param mixed $filter
	 * @param Request $request
	 * @return mixed
	 */
	public function scope(mixed $filter, Request $request): mixed
	{
		return $filter;
	}
}
