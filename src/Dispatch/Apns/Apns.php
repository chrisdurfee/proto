<?php declare(strict_types=1);
namespace Proto\Dispatch\Apns;

use Proto\Dispatch\Dispatch;
use Proto\Dispatch\Response;

/**
 * Apns
 *
 * Sends notifications to Apple devices over the APNs HTTP/2 API using
 * token-based (p8 / ES256) authentication. Used for native-shell app
 * installs where Web Push is unavailable (WKWebView has no PushManager).
 *
 * Config lives under `push.apns` in the app env:
 * `{ "keyId": "...", "teamId": "...", "bundleId": "...", "keyPath": "/path/AuthKey.p8", "sandbox": false }`
 *
 * @package Proto\Dispatch\Apns
 */
class Apns extends Dispatch
{
	/**
	 * @var string
	 */
	protected const HOST_PRODUCTION = 'https://api.push.apple.com';

	/**
	 * @var string
	 */
	protected const HOST_SANDBOX = 'https://api.sandbox.push.apple.com';

	/**
	 * APNs reasons that mean the device token is permanently dead.
	 *
	 * @var array<int, string>
	 */
	protected const DEAD_TOKEN_REASONS = [
		'BadDeviceToken',
		'Unregistered',
		'DeviceTokenNotForTopic',
		'ExpiredToken'
	];

	/**
	 * @param array<int, string> $tokens APNs device tokens (hex strings).
	 * @param string $payload The APNs JSON payload.
	 */
	public function __construct(
		protected array $tokens = [],
		protected string $payload = ''
	)
	{
	}

	/**
	 * Reads and validates the APNs settings.
	 *
	 * @return object|null The settings, or null when unconfigured.
	 */
	protected function getSettings(): ?object
	{
		$settings = env('push')->apns ?? null;
		if (empty($settings->keyId) || empty($settings->teamId) || empty($settings->bundleId) || empty($settings->keyPath))
		{
			return null;
		}

		return $settings;
	}

	/**
	 * Sends the notifications and returns a response. Tokens Apple
	 * reports as dead are returned in the response data under
	 * `invalidTokens` so callers can deactivate them.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		$settings = $this->getSettings();
		if ($settings === null)
		{
			return $this->error('APNs is not configured.');
		}

		if (empty($this->tokens))
		{
			return $this->error('No APNs device tokens to send to.');
		}

		$jwt = ApnsJwt::create((string)$settings->keyId, (string)$settings->teamId, (string)$settings->keyPath);
		if ($jwt === null)
		{
			return $this->error('Failed to create the APNs provider token.');
		}

		$host = !empty($settings->sandbox) ? self::HOST_SANDBOX : self::HOST_PRODUCTION;
		$topic = (string)$settings->bundleId;
		$sent = 0;
		$invalidTokens = [];

		foreach ($this->tokens as $token)
		{
			$result = $this->sendOne($host, $jwt, $topic, (string)$token);
			if ($result->status === 200)
			{
				$sent++;
				continue;
			}

			if (in_array($result->reason, self::DEAD_TOKEN_REASONS, true))
			{
				$invalidTokens[] = (string)$token;
			}
		}

		$error = ($sent === 0);
		$message = $error ? 'Failed to send APNs notifications.' : 'APNs notifications sent.';
		return $this->response($error, $message, (object)[
			'sent' => $sent,
			'invalidTokens' => $invalidTokens
		]);
	}

	/**
	 * Sends a single notification over HTTP/2.
	 *
	 * @param string $host The APNs host.
	 * @param string $jwt The provider token.
	 * @param string $topic The apns-topic (bundle ID).
	 * @param string $token The device token.
	 * @return object `{ status: int, reason: string }`
	 */
	protected function sendOne(string $host, string $jwt, string $topic, string $token): object
	{
		$ch = curl_init($host . '/3/device/' . $token);
		curl_setopt_array($ch, [
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $this->payload,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTPHEADER => [
				'authorization: bearer ' . $jwt,
				'apns-topic: ' . $topic,
				'apns-push-type: alert',
				'apns-priority: 10',
				'content-type: application/json'
			]
		]);

		$body = curl_exec($ch);
		if ($body === false)
		{
			error_log('[Apns] request failed: ' . curl_error($ch));
			curl_close($ch);
			return (object)['status' => 0, 'reason' => 'ConnectionError'];
		}

		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);

		$reason = '';
		if ($status !== 200)
		{
			$reason = (string)(json_decode((string)$body)->reason ?? '');
			error_log('[Apns] send returned ' . $status . ' (' . $reason . ')');
		}

		return (object)['status' => $status, 'reason' => $reason];
	}
}
