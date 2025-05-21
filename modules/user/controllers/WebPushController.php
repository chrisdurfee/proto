<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\PushPolicy;
use Proto\Controllers\Controller;
use Modules\User\Models\WebPushUser;
use Modules\User\Models\NotificationPreference;
use Proto\Http\Router\Request;

/**
 * WebPushController
 *
 * This controller handles web push notifications for users.
 *
 * @package Modules\User\Controllers
 */
class WebPushController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = PushPolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param WebPushUser $pushUser The WebPushUser model instance.
	 * @param NotificationPreference $notificationPreference The NotificationPreference model instance.
	 */
	public function __construct(
		protected WebPushUser $pushUser = new WebPushUser(),
		protected NotificationPreference $notificationPreference = new NotificationPreference()
	)
	{
		parent::__construct();
	}

	/**
	 * This will get the request item from the request.
	 *
	 * @param Request $request
	 * @return object|null
	 */
	protected function getRequestItem(Request $request): ?object
	{
		$item = $request->json('item');
		if (!isset($item))
		{
			return null;
		}

		return (object)[
			'userId' => $request->params()->id,
			'endpoint' => $item->subscription->endpoint ?? '',
			'authKeys' => json_encode($item->subscription->keys ?? ''),
			'status' => 'active'
		];
	}

	/**
	 * This will add or update the notification preference.
	 *
	 * @param mixed $userId
	 * @param int $status
	 * @return bool
	 */
	protected function updateNotificationPreference(mixed $userId, int $status = 1): bool
	{
		$this->notificationPreference->userId = $userId;
		$this->notificationPreference->status = $status;

		return $this->notificationPreference->merge();
	}

	/**
	 * This will subscribe the user to web push notifications.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function subscribe(Request $request): object
	{
		$item = $this->getRequestItem($request);
		if (!isset($item))
		{
			return $this->error('No user found.');
		}

		$this->updateNotificationPreference($item->userId, 1);
		$this->pushUser->set((object)[
			'userId' => $item->userId,
			'endpoint' => $item->endpoint,
			'authKeys' => $item->authKeys,
			'status' => $item->status
		]);

		$result = $this->pushUser->setup();
		return $this->response($result);
	}

	/**
	 * This will unsubscribe the user from web push notifications.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function unsubscribe(Request $request): object
	{
		$item = $this->getRequestItem($request);
		if (!isset($item))
		{
			return $this->error('No user found.');
		}

		$this->updateNotificationPreference($item->userId, 0);
		return $this->pushUser->updateStatusByKey($item->authKeys, 'inactive');
	}
}