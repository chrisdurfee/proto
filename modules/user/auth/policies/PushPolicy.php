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
     * This will check if the user can subscribe to web push notifications.
     *
     * @param Request $request
     * @return bool
     */
	public function subscribe(Request $request): bool
    {
        $userId = $this->getResourceId($request);
        return $this->ownsResource($userId);
    }

    /**
     * This will check if the user can unsubscribe from web push notifications.
     *
     * @param Request $request
     * @return bool
     */
    public function unsubscribe(Request $request): bool
    {
        $userId = $this->getResourceId($request);
        return $this->ownsResource($userId);
    }
}