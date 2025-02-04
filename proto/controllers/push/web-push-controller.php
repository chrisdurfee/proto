<?php declare(strict_types=1);
namespace Proto\Controllers\Push;

use Proto\Models\WebPushUser;
use Proto\Dispatch\Dispatcher;

/**
 * WebPushController
 *
 * This will be the controller for web push notifications.
 *
 * @package Proto\Controllers\Push
 */
class WebPushController extends PushController
{
	/**
	 * This will set up the model class.
	 *
	 * @return string
	 */
	protected function getModelClass()
	{
		return WebPushUser::class;
	}

	/**
	 * This will get the subscriptions.
	 *
	 * @param mixed $clientId
	 * @param string|null $type
	 * @return array
	 */
	protected function getSubscriptions($clientId, ?string $type = null): array
	{
		return $this->model()->getByClientId($clientId, $type);
	}

	/**
	 * This will deactivate a subscription.
	 *
	 * @param int $id
	 * @return bool
	 */
	protected function deactivate($id): bool
	{
		$model = $this->model((object)[
			'id' => $id,
			'status' => 'inactive'
		]);
		return $model->updateStatus();
	}

	/**
	 * This will create the settings object.
	 *
	 * @param array $subscriptions
	 * @param string $template
	 * @return object
	 */
	protected static function createSettings(array $subscriptions, string $template): object
	{
		return (object)[
			'subscriptions' => $subscriptions,
			'template' => $template
		];
	}

	/**
	 * This will get the subscription by endpoint.
	 *
	 * @param array $subscriptions
	 * @param string $endpoint
	 * @return array|null
	 */
	protected static function getSubscriptionByEnd(array &$subscriptions, string $endpoint): ?array
	{
		foreach($subscriptions as $subscription)
		{
			if($subscription['endpoint'] === $endpoint)
			{
				return $subscription;
			}
		}
		return null;
	}

	/**
	 * This will format a subscription.
	 *
	 * @param object|null $subscription
	 * @return array
	 */
	protected function formatSubscription(?object $subscription): array
	{
		return [
			'id' => $subscription->id,
			'endpoint' => $subscription->endpoint,
			'keys' => [
				'auth' => $subscription->authKeys->auth,
				'p256dh' => $subscription->authKeys->p256dh
			]
		];
	}

	protected function batch(array $subscriptions, string $template, ?object $data = null)
	{
		$pushSubscriptions = [];
		foreach($subscriptions as $subscription)
		{
			array_push($pushSubscriptions, $this->formatSubscription($subscription));
		}

		$settings = self::createSettings($pushSubscriptions, $template);

		$result = Dispatcher::push($settings, $data);
		$this->deactivateSubscriptions($pushSubscriptions, $result);

		return true;
	}

	/**
	 * This will deactivate invalid subscriptions.
	 *
	 * @param array $subscriptions
	 * @param object $result
	 * @return void
	 */
	protected function deactivateSubscriptions(array $subscriptions, object $result)
	{
		$rows = $result->getData();
		foreach ($rows as $report)
		{
			if ($report->sent === false)
			{
				$subscription = self::getSubscriptionByEnd($subscriptions, $report->endpoint);
				if ($subscription)
				{
					$this->deactivate($subscription['id']);
				}
			}
		}
	}

	public function send($clientId, string $template, ?object $data = null, ?string $type = null)
	{
		$subscriptions = $this->getSubscriptions($clientId, $type);
		if(count($subscriptions) < 1)
		{
			return false;
		}

		return $this->batch($subscriptions, $template, $data);
	}

	public static function dispatch($clientId, string $template, ?object $data = null, ?string $type = null)
	{
		$controller = new static();
		return $controller->send($clientId, $template, $data, $type);
	}
}