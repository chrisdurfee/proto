<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

use Proto\Http\Router\Request;

/**
 * Class PushPolicy
 *
 * Policy that governs access control for managing push notifications.
 *
 * @package Modules\User\Auth\Policies
 */
class PushPolicy extends Policy
{
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