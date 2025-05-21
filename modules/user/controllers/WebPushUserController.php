<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\NotificationPreference;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\WebPushUser;
use Proto\Dispatch\Dispatcher;

/**
 * WebPushUserController
 *
 * @package Modules\User\Controllers
 */
class WebPushUserController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = WebPushUser::class)
	{
		parent::__construct();
	}

	protected function canPush(
		mixed $userId,
		NotificationPreference $preference = NotificationPreference::class
	): bool
	{
		$model = new $preference();
		$preference = $model->getBy([
			['user_id', $userId]
		]);
		return $preference?->allowPush ?? false;
	}

	/**
	 * Sends a web push notification to the user.
	 *
	 * @param mixed $userId The user ID to send the notification to.
	 * @param object $settings The settings for the notification.
	 * @param object|null $data Optional data for the notification.
	 * @return object|null The response from the dispatcher or null if user ID is not set.
	 */
	public function send(mixed $userId, object $settings, ?object $data = null): ?object
	{
		if (!isset($userId))
		{
			return null;
		}

		$model = new ($this->modelClass)();
		$subscriptions = $model->getByUser($userId);
		if (!isset($subscriptions))
		{
			return null;
		}

		$settings->subscriptions = $subscriptions;
		return Dispatcher::push($settings, $data);
	}
}