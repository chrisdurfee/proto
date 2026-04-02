<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

use Proto\Controllers\Controller;
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
	 * @param ?Controller $controller The controller instance associated with this policy.
	 * @return void
	 */
	public function __construct(protected ?Controller $controller = null)
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
		if (!$this->isDevelopment())
		{
			return;
		}

		$this->validateTypeProperty();
		$this->validateMethodSignatures();
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
	 * Policy methods for standard actions (get, add, update, delete, all, search)
	 * should accept a single Request parameter. Methods with wrong signatures
	 * will silently fall through to default() — this validation catches that.
	 *
	 * @return void
	 */
	protected function validateMethodSignatures(): void
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

			trigger_error(
				"Policy method " . static::class . "::{$method}() has an unexpected signature. "
				. "Expected (Request \$request): bool or no parameters. "
				. "The current signature will cause the method to never be called by the policy dispatcher.",
				E_USER_WARNING
			);
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
	 * Checks if the given user ID matches the current session user.
	 *
	 * This is the simplest ownership check — use for resources that
	 * have a direct userId field.
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
}
