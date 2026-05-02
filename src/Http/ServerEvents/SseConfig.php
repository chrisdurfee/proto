<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

/**
 * SseConfig
 *
 * Centralised configuration for Server-Sent Events streams.
 *
 * Defaults are conservative and chosen so that:
 *   1. Every PHP-FPM worker handling an SSE stream is guaranteed to recycle
 *      within `maxDuration` seconds, even if the client never disconnects
 *      and every other safety mechanism fails.
 *   2. The browser's `EventSource` will silently auto-reconnect after the
 *      stream ends, so the user never notices.
 *
 * Apps may override defaults by adding an `sse` block to `common/Config/.env`:
 *
 * ```json
 * {
 *   "sse": {
 *     "maxDuration": 300,
 *     "heartbeatInterval": 15,
 *     "redisReadTimeout": 2,
 *     "maxReconnectFailures": 3,
 *     "shutdownGrace": 30
 *   }
 * }
 * ```
 *
 * Per-stream overrides are supported by passing an array to the
 * `RedisServerEvents` / `ServerEvents` constructors, or via the
 * `redisEvent()` / `serverEvent()` helper functions.
 *
 * @package Proto\Http\ServerEvents
 */
final class SseConfig
{
	/**
	 * Maximum stream duration in seconds before the server cleanly ends the
	 * stream (the client will auto-reconnect).
	 *
	 * @var int
	 */
	public int $maxDuration;

	/**
	 * How often to emit an SSE heartbeat / poll for client disconnect.
	 *
	 * @var int
	 */
	public int $heartbeatInterval;

	/**
	 * Redis pub/sub read timeout in seconds. When this elapses, `subscribe()`
	 * returns control so the loop can run heartbeat + cancellation checks.
	 *
	 * @var int
	 */
	public int $redisReadTimeout;

	/**
	 * Maximum consecutive Redis reconnect failures before giving up.
	 *
	 * @var int
	 */
	public int $maxReconnectFailures;

	/**
	 * Extra seconds added on top of `maxDuration` when calling
	 * `set_time_limit()` so cleanup code can run before PHP forcibly aborts.
	 *
	 * @var int
	 */
	public int $shutdownGrace;

	/**
	 * @param array<string, int> $overrides Optional per-stream overrides.
	 */
	public function __construct(array $overrides = [])
	{
		$env = $this->loadEnvDefaults();

		$this->maxDuration          = $this->resolve($overrides, $env, 'maxDuration', 300);
		$this->heartbeatInterval    = $this->resolve($overrides, $env, 'heartbeatInterval', 15);
		$this->redisReadTimeout     = $this->resolve($overrides, $env, 'redisReadTimeout', 2);
		$this->maxReconnectFailures = $this->resolve($overrides, $env, 'maxReconnectFailures', 3);
		$this->shutdownGrace        = $this->resolve($overrides, $env, 'shutdownGrace', 30);

		$this->maxDuration          = max(1, $this->maxDuration);
		$this->heartbeatInterval    = max(1, $this->heartbeatInterval);
		$this->redisReadTimeout     = max(1, $this->redisReadTimeout);
		$this->maxReconnectFailures = max(0, $this->maxReconnectFailures);
		$this->shutdownGrace        = max(0, $this->shutdownGrace);
	}

	/**
	 * Reads SSE defaults from `env('sse')` (an stdClass) into a flat array.
	 *
	 * @return array<string, int>
	 */
	protected function loadEnvDefaults(): array
	{
		$out = [];
		if (!function_exists('env'))
		{
			return $out;
		}

		try
		{
			$cfg = env('sse');
		}
		catch (\Throwable $e)
		{
			return $out;
		}

		if (!is_object($cfg))
		{
			return $out;
		}

		foreach (['maxDuration', 'heartbeatInterval', 'redisReadTimeout', 'maxReconnectFailures', 'shutdownGrace'] as $k)
		{
			if (isset($cfg->{$k}) && is_numeric($cfg->{$k}))
			{
				$out[$k] = (int)$cfg->{$k};
			}
		}

		return $out;
	}

	/**
	 * @param array<string, int> $overrides
	 * @param array<string, int> $env
	 */
	protected function resolve(array $overrides, array $env, string $key, int $default): int
	{
		if (array_key_exists($key, $overrides))
		{
			return (int)$overrides[$key];
		}

		if (array_key_exists($key, $env))
		{
			return $env[$key];
		}

		return $default;
	}

	/**
	 * Returns the wall-clock deadline (Unix timestamp) for a stream that
	 * starts now.
	 *
	 * @return float
	 */
	public function deadlineFromNow(): float
	{
		return microtime(true) + $this->maxDuration;
	}

	/**
	 * Total CPU/wall budget given to the script (max duration + grace).
	 *
	 * @return int
	 */
	public function scriptTimeLimit(): int
	{
		return $this->maxDuration + $this->shutdownGrace;
	}
}
