<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

/**
 * SseFormat
 *
 * Byte-exact SSE frames. Matches what `RedisServerEvents` writes today
 * (`event: message` + `data:`, `: heartbeat` comments) so clients see no
 * difference between a PHP-FPM stream and a realtime-server stream.
 *
 * @package Proto\Realtime\Server
 */
final class SseFormat
{
	/**
	 * A `message` event. Multi-line data becomes one `data:` line per
	 * line, per the SSE spec, so the browser rejoins it with "\n".
	 *
	 * @param string $data Usually a JSON string.
	 * @return string
	 */
	public static function message(string $data): string
	{
		$lines = preg_split('/\r\n|\r|\n/', $data) ?: [$data];
		$frame = "event: message\n";
		foreach ($lines as $line)
		{
			$frame .= 'data: ' . $line . "\n";
		}

		return $frame . "\n";
	}

	/**
	 * A comment line; browsers ignore it. Used for heartbeats.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function comment(string $text): string
	{
		return ': ' . str_replace(["\r", "\n"], ' ', $text) . "\n\n";
	}

	/**
	 * Reconnect delay hint for the browser's EventSource.
	 *
	 * @param int $milliseconds
	 * @return string
	 */
	public static function retry(int $milliseconds): string
	{
		return 'retry: ' . max(0, $milliseconds) . "\n\n";
	}

	/**
	 * Encode a hydrated result the way `RedisServerEvents::sendMessage()`
	 * does: JSON, or nothing when it cannot be encoded.
	 *
	 * @param mixed $result
	 * @return string|null
	 */
	public static function encode(mixed $result): ?string
	{
		$json = json_encode($result);
		return $json === false ? null : $json;
	}
}
