<?php declare(strict_types=1);
namespace Proto\Realtime;

use Proto\Http\HttpTerminationException;

/**
 * RealtimeBridge
 *
 * PHP-FPM side of the realtime server. The realtime server forwards a
 * browser's original `GET .../sync` request back to PHP with a shared
 * secret. The route, middleware, and policy run exactly as for a
 * normal stream; when the controller reaches `redisEvent()`, this class
 * answers with JSON instead of opening a long-lived stream:
 *
 *  - authorize: which channels this viewer may subscribe to, plus the
 *    identity used for the stale-connection key and user kick channel.
 *  - hydrate: run the controller's per-message callback for one
 *    published message and return its result.
 *
 * Normal browser requests never carry the secret, so they fall through
 * to the classic `RedisServerEvents` stream.
 *
 * @package Proto\Realtime
 */
final class RealtimeBridge
{
	/**
	 * `$_SERVER` key of the shared-secret header.
	 *
	 * @var string
	 */
	public const SECRET_HEADER = 'HTTP_X_PROTO_REALTIME';

	/**
	 * `$_SERVER` key of the mode header.
	 *
	 * @var string
	 */
	public const MODE_HEADER = 'HTTP_X_PROTO_REALTIME_MODE';

	/**
	 * @var string
	 */
	public const MODE_AUTHORIZE = 'authorize';

	/**
	 * @var string
	 */
	public const MODE_HYDRATE = 'hydrate';

	/**
	 * Forward the published payload untouched (no PHP per message).
	 *
	 * @var string
	 */
	public const HYDRATE_RAW = 'raw';

	/**
	 * Run the callback once per message and send the result to every
	 * viewer of the same stream. Only for output that does not depend
	 * on who is watching.
	 *
	 * @var string
	 */
	public const HYDRATE_SHARED = 'shared';

	/**
	 * Run the callback once per message per viewer (safe default).
	 *
	 * @var string
	 */
	public const HYDRATE_VIEWER = 'viewer';

	/**
	 * Same rule the publish side enforces (`Events`), so a descriptor can
	 * never name a channel publishers could not use.
	 *
	 * @var string
	 */
	private const CHANNEL_PATTERN = '/^[a-zA-Z0-9._:\-]+$/';

	/**
	 * Active mode for this request when it carries a valid secret, else
	 * null.
	 *
	 * @param array<string, mixed>|null $server Defaults to `$_SERVER`
	 * @param string|null $secret Defaults to `realtime.secret` from env
	 * @return string|null
	 */
	public static function mode(?array $server = null, ?string $secret = null): ?string
	{
		$server ??= $_SERVER;
		$secret ??= self::configuredSecret();
		if ($secret === '')
		{
			return null;
		}

		$sent = $server[self::SECRET_HEADER] ?? null;
		if (!is_string($sent) || !hash_equals($secret, $sent))
		{
			return null;
		}

		$mode = $server[self::MODE_HEADER] ?? '';
		return in_array($mode, [self::MODE_AUTHORIZE, self::MODE_HYDRATE], true) ? $mode : null;
	}

	/**
	 * Answer a realtime-server request from inside `redisEvent()`.
	 * Returns normally when this is a plain browser request.
	 *
	 * @param array|string $channels
	 * @param callable|null $callback
	 * @param string $hydrate One of the HYDRATE_* modes.
	 * @return void
	 * @throws HttpTerminationException With the JSON answer when the
	 *   request came from the realtime server.
	 */
	public static function intercept(array|string $channels, ?callable $callback, string $hydrate = self::HYDRATE_VIEWER): void
	{
		$mode = self::mode();
		if ($mode === null)
		{
			return;
		}

		if ($mode === self::MODE_HYDRATE)
		{
			throw new HttpTerminationException(
				self::hydrate($callback, (string)file_get_contents('php://input')),
				200
			);
		}

		throw new HttpTerminationException(
			self::describe($channels, $callback, $hydrate, self::viewer()),
			200
		);
	}

	/**
	 * Authorize response: channels plus the identity the realtime server
	 * needs for the stale-connection key and the user kick channel.
	 *
	 * @param array|string $channels
	 * @param callable|null $callback
	 * @param string $hydrate
	 * @param object $viewer {userId, sessionId, sseClient}
	 * @return array<string, mixed>
	 */
	public static function describe(array|string $channels, ?callable $callback, string $hydrate, object $viewer): array
	{
		$list = [];
		foreach ((is_array($channels) ? $channels : [$channels]) as $channel)
		{
			$channel = (string)$channel;
			if ($channel !== '' && preg_match(self::CHANNEL_PATTERN, $channel) === 1)
			{
				$list[] = $channel;
			}
		}

		if (!in_array($hydrate, [self::HYDRATE_RAW, self::HYDRATE_SHARED, self::HYDRATE_VIEWER], true))
		{
			$hydrate = self::HYDRATE_VIEWER;
		}

		return [
			'allow' => $list !== [],
			'channels' => array_values(array_unique($list)),
			'hydrate' => $callback === null ? self::HYDRATE_RAW : $hydrate,
			'userId' => (string)($viewer->userId ?? 'guest'),
			'sessionId' => (string)($viewer->sessionId ?? ''),
			'sseClient' => (string)($viewer->sseClient ?? '')
		];
	}

	/**
	 * Hydrate response for one published message. The body is
	 * `{"channel": "...", "message": "<raw published string>"}`, decoded
	 * the same way `RedisServerEvents` decodes it.
	 *
	 * @param callable|null $callback
	 * @param string $body
	 * @return array{result: mixed, close: bool}
	 */
	public static function hydrate(?callable $callback, string $body): array
	{
		$input = json_decode($body, true);
		$channel = is_array($input) ? (string)($input['channel'] ?? '') : '';
		$raw = is_array($input) ? ($input['message'] ?? null) : null;
		$payload = is_string($raw) ? (json_decode($raw, true) ?? $raw) : $raw;

		if ($callback === null)
		{
			return ['result' => $payload, 'close' => false];
		}

		$result = $callback($channel, $payload);
		if ($result === false)
		{
			return ['result' => null, 'close' => true];
		}

		return ['result' => $result, 'close' => false];
	}

	/**
	 * Current viewer from the session plus the per-tab `sseClient`.
	 *
	 * @return object
	 */
	private static function viewer(): object
	{
		$session = function_exists('session') ? session() : null;
		$sessionId = $session !== null ? (string)$session::getId() : '';
		$client = $_GET['sseClient'] ?? '';

		return (object)[
			'userId' => (string)($session?->user->id ?? 'guest'),
			'sessionId' => $sessionId,
			'sseClient' => (is_string($client) && preg_match('/^[A-Za-z0-9_-]{8,64}$/', $client) === 1) ? $client : ''
		];
	}

	/**
	 * @return string
	 */
	private static function configuredSecret(): string
	{
		$config = function_exists('env') ? env('realtime') : null;
		return is_object($config) ? (string)($config->secret ?? '') : '';
	}
}
