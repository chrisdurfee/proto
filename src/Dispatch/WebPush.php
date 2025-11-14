<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Minishlink\WebPush\WebPush as wPush;
use Minishlink\WebPush\Subscription;

/**
 * Class WebPush
 *
 * This class sends web push notifications using the Minishlink library.
 *
 * @package Proto\Dispatch
 */
class WebPush extends Dispatch
{
	/**
	 * Instance of the WebPush library.
	 *
	 * @var wPush|null
	 */
	protected static ?wPush $webPush = null;

	/**
	 * List of subscription settings.
	 *
	 * @var array
	 */
	protected array $subscriptions;

	/**
	 * Notification payload.
	 *
	 * @var string
	 */
	protected string $payload;

	/**
	 * WebPush constructor.
	 *
	 * @param array $settings An array of subscription settings.
	 * @param string $payload The push notification payload.
	 */
	public function __construct(array $settings = [], string $payload = '')
	{
		$this->setupWebPush();
		$this->subscriptions = $settings;
		$this->payload = $payload;
	}

	/**
	 * Retrieves the authentication settings for push notifications.
	 *
	 * @return array
	 */
	protected function getAuthSettings() : array
	{
		$settings = env('push');
		if (empty($settings->auth))
		{
			return [];
		}

		$auth = (array)$settings->auth;
		$auth['VAPID'] = (array)$auth['VAPID'];
		return $auth;
	}

	/**
	 * Sets up the WebPush instance using the authentication settings.
	 *
	 * @return void
	 */
	protected function setupWebPush() : void
	{
		if (self::$webPush !== null)
		{
			return;
		}

		$auth = $this->getAuthSettings();

		self::$webPush = new wPush($auth);
		self::$webPush->setReuseVAPIDHeaders(true);
		self::$webPush->setAutomaticPadding(false);
	}

	/**
	 * Creates a subscription object from the provided settings.
	 *
	 * @param array $settings Subscription settings.
	 *
	 * @return Subscription|null Returns a Subscription object or null if settings are empty.
	 */
	protected function setupSubscription(array $settings = []) : ?Subscription
	{
		if (empty($settings))
		{
			return null;
		}

		if (!isset($settings['keys']))
		{
			$settings['keys'] = [
				'auth' => $settings['authKeys']['auth'] ?? null,
				'p256dh' => $settings['authKeys']['p256dh'] ?? null
			];
		}

		return Subscription::create($settings);
	}

	/**
	 * Queues and sends notifications for multiple subscriptions.
	 *
	 * @return object An object containing a success flag and an array of report rows.
	 */
	public function batch() : object
	{
		// Queue notifications for valid subscriptions.
		foreach ($this->subscriptions as $subscription)
		{
			$sub = $this->setupSubscription($subscription);
			if ($sub === null)
			{
				continue;
			}

			self::$webPush->queueNotification(
				$sub,
				$this->payload
			);
		}

		$result = (object)[
			'rows' => [],
			'success' => true
		];

		// Process reports from flush.
		foreach (self::$webPush->flush() as $report)
		{
			$sent = $report->isSuccess();
			if (!$sent)
			{
				$result->success = false;
			}

			$result->rows[] = (object)[
				'endpoint' => $report->getEndpoint(),
				'sent' => $sent
			];
		}

		return $result;
	}

	/**
	 * Sends a single push notification.
	 *
	 * @param array $subscription A single subscription setting.
	 *
	 * @return bool True if the notification was sent successfully, false otherwise.
	 */
	protected function sendOne(array $subscription) : bool
	{
		$sub = $this->setupSubscription($subscription);
		if ($sub === null)
		{
			return false;
		}

		self::$webPush->queueNotification(
			$sub,
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
	 * Sends the web push notifications and returns a response.
	 *
	 * @return Response
	 */
	public function send() : Response
	{
		$result = $this->batch();
		return Response::create($result->success, '', $result->rows);
	}
}