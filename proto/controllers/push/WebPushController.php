<?php declare(strict_types=1);
namespace Proto\Controllers\Push;

use Proto\Models\WebPushUser;
use Proto\Dispatch\Dispatcher;

/**
 * WebPushController
 *
 * Controller for web push notifications.
 *
 * @package Proto\Controllers\Push
 */
class WebPushController extends PushController
{
	/**
	 * Sets up the model class.
	 *
	 * @return string
	 */
	protected function getModelClass(): string
	{
		return WebPushUser::class;
	}

	/**
	 * Gets the subscriptions.
	 *
	 * @param mixed $clientId The client identifier.
	 * @param string|null $type The subscription type.
	 * @return array
	 */
	protected function getSubscriptions(mixed $clientId, ?string $type = null): array
	{
		return $this->model()->getByClientId($clientId, $type);
	}

	/**
	 * Deactivates a subscription.
	 *
	 * @param int $id The subscription ID.
	 * @return bool
	 */
	protected function deactivate(int $id): bool
	{
		$model = $this->model((object)[
			'id' => $id,
			'status' => 'inactive'
		]);
		return $model->updateStatus();
	}

	/**
	 * Creates the settings object.
	 *
	 * @param array $subscriptions The subscriptions.
	 * @param string $template The template identifier.
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
	 * Gets the subscription by endpoint.
	 *
	 * @param array $subscriptions The list of subscriptions.
	 * @param string $endpoint The endpoint to match.
	 * @return array|null
	 */
	protected static function getSubscriptionByEnd(array &$subscriptions, string $endpoint): ?array
	{
		foreach ($subscriptions as $subscription)
		{
			if ($subscription['endpoint'] === $endpoint)
			{
				return $subscription;
			}
		}
		return null;
	}

	/**
	 * Formats a subscription.
	 *
	 * @param object|null $subscription The subscription object.
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

	/**
	 * Batches subscriptions and dispatches push notifications.
	 *
	 * @param array $subscriptions The list of subscriptions.
	 * @param string $template The template identifier.
	 * @param object|null $data Optional data for the push.
	 * @return bool
	 */
	protected function batch(array $subscriptions, string $template, ?object $data = null): bool
	{
		$pushSubscriptions = [];
		foreach ($subscriptions as $subscription)
		{
			$pushSubscriptions[] = $this->formatSubscription($subscription);
		}

		$settings = self::createSettings($pushSubscriptions, $template);
		$result = Dispatcher::push($settings, $data);
		$this->deactivateSubscriptions($pushSubscriptions, $result);
		return true;
	}

	/**
	 * Deactivates invalid subscriptions.
	 *
	 * @param array $subscriptions The list of subscriptions.
	 * @param object $result The push dispatch result.
	 * @return void
	 */
	protected function deactivateSubscriptions(array $subscriptions, object $result): void
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

	/**
	 * Sends push notifications.
	 *
	 * @param mixed $clientId The client identifier.
	 * @param string $template The template identifier.
	 * @param object|null $data Optional push data.
	 * @param string|null $type Optional subscription type.
	 * @return bool
	 */
	public function send(mixed $clientId, string $template, ?object $data = null, ?string $type = null): bool
	{
		$subscriptions = $this->getSubscriptions($clientId, $type);
		if (count($subscriptions) < 1)
		{
			return false;
		}
		return $this->batch($subscriptions, $template, $data);
	}

	/**
	 * Dispatches push notifications.
	 *
	 * @param mixed $clientId The client identifier.
	 * @param string $template The template identifier.
	 * @param object|null $data Optional push data.
	 * @param string|null $type Optional subscription type.
	 * @return bool
	 */
	public static function dispatch(mixed $clientId, string $template, ?object $data = null, ?string $type = null): bool
	{
		$controller = new static();
		return $controller->send($clientId, $template, $data, $type);
	}
}