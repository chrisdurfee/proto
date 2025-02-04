<?php declare(strict_types=1);
namespace Proto\Dispatch;

include_once __DIR__ . '/../../vendor/autoload.php';

use Proto\Config;
use Minishlink\WebPush\WebPush as wPush;
use Minishlink\WebPush\Subscription;

/**
 * WebPush
 *
 * This will send a web push notification.
 *
 * @package Proto\Dispatch
 */
class WebPush extends Dispatch
{
	/**
	 * @var wPush $webPush
	 */
	protected static $webPush;

	/**
	 * @var array $subscriptions
	 */
	protected array $subscriptions;

	/**
	 * @var string $payload
	 */
	protected string $payload;

	/**
	 * @var array $auth
	 */
	protected array $auth;

	/**
	 *
	 * @param array $settings
	 * @param string $payload
	 */
	public function __construct(
		array $settings = [],
		string $payload = ''
	)
	{
		$this->setupWebPush();
		$this->subscriptions = $settings;
		$this->payload = $payload;
	}

	/**
	 * This will get the push auth settings.
	 *
	 * @return array
	 */
	protected function getAuthSettings(): array
	{
		$settings = Config::getInstance();
		if (empty($settings->push->auth))
		{
			return [];
		}

		$auth = (array)$settings->push->auth;
		$auth['VAPID'] = (array)$auth['VAPID'];
		return $auth;
	}

	/**
	 * This will setup the wPush using our auth settings.
	 *
	 * @return void
	 */
	protected function setupWebPush(): void
	{
		if (isset(self::$webPush))
		{
			return;
		}

		$auth = $this->getAuthSettings();

		self::$webPush = new wPush($auth);
		self::$webPush->setReuseVAPIDHeaders(true);
		self::$webPush->setAutomaticPadding(false);
	}

	/**
	 * This will setup the subscription.
	 *
	 * @param array $settings
	 * @return Subscription|null
	 */
	protected function setupSubscription(array $settings = []): ?Subscription
	{
		if (count($settings) < 1)
		{
			return null;
		}

		return Subscription::create($settings);
	}

	/**
	 * This will batch multiple notifications.
	 *
	 * @return object
	 */
	public function batch(): object
	{
		$subscriptions = $this->subscriptions;
		foreach ($subscriptions as $subscription)
		{
			self::$webPush->queueNotification(
				$this->setupSubscription($subscription),
				$this->payload
			);
		}

		$result = (object)[
			'rows' => [],
			'success' => true
		];

		foreach (self::$webPush->flush() as $report)
		{
			$sent = $report->isSuccess();
			if (!$sent)
			{
				$result->success = false;
			}

			array_push($result->rows, (object)[
				'endpoint' => $report->getEndpoint(),
				'sent' => $sent
			]);
		}
		return $result;
	}

	/**
	 * This will send one push notification.
	 *
	 * @param array $subscription
	 * @return bool
	 */
	protected function sendOne(array $subscription): bool
	{
		self::$webPush->queueNotification(
			$this->setupSubscription($subscription),
			$this->payload
		);

		$sent = false;
		foreach (self::$webPush->flush() as $report)
		{
			$sent = $report->isSuccess();
			break;
		}
		return $sent;
	}

	/**
	 * This will send the notification.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		$result = $this->batch();
		return Response::create($result->success, '', $result->rows);
	}
}