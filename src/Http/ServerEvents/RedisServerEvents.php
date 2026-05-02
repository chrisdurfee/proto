<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Cache\Cache;
use Proto\Utils\Filter\Input;

/**
 * RedisServerEvents
 *
 * Implements Server-Sent Events (SSE) directly with Redis pub/sub.
 * Does not use EventLoop since Redis subscribe() is already blocking.
 *
 * Reliability guarantees:
 *   - Stream always exits within `SseConfig::$maxDuration` seconds (the
 *     browser's EventSource auto-reconnects, so this is invisible to
 *     users).
 *   - Heartbeats use `fwrite(php://output, ...)` and detect broken pipes,
 *     so a closed client connection terminates the stream within one
 *     `redisReadTimeout` window instead of leaking the worker forever.
 *   - A `register_shutdown_function` ensures Redis cleanup runs even on
 *     fatal errors / `request_terminate_timeout` kills (where `__destruct`
 *     would not).
 *
 * @package Proto\Http\ServerEvents
 */
class RedisServerEvents
{
	use ServerEventsTrait;

	/**
	 * @var \Redis Redis connection for pub/sub.
	 * @SuppressWarnings PHP0413
	 */
	protected \Redis $connection;

	/**
	 * @var array Connection settings.
	 */
	protected array $settings;

	/**
	 * @var bool Whether the connection is active.
	 */
	protected bool $active = true;

	/**
	 * @var string Unique identifier for this connection.
	 */
	protected string $connectionId;

	/**
	 * Constructor.
	 *
	 * @param array|null $settings Optional Redis connection settings
	 *   (`host`, `port`, `password`). When null, pulled from `env('cache')`.
	 * @param array<string, int>|SseConfig|null $config Optional SSE config
	 *   overrides (see `SseConfig`) or a pre-built config instance.
	 */
	public function __construct(?array $settings = null, array|SseConfig|null $config = null)
	{
		$this->sseConfig = $config instanceof SseConfig
			? $config
			: new SseConfig($config ?? []);

		$this->settings = $settings ?? $this->getDefaultSettings();
		$this->connectionId = $this->generateConnectionId();
		$this->configureStreaming();
		$this->setupResponse();
		$this->registerShutdownHandler(function(): void
		{
			// Idempotent — safe to call multiple times.
			$this->close();
		});
		$this->closeStaleConnections();
		$this->registerConnection();
		$this->connectToRedis();
	}

	/**
	 * Gets default Redis connection settings from the environment.
	 *
	 * @return array
	 */
	protected function getDefaultSettings(): array
	{
		$connection = env('cache')?->connection ?? null;

		return [
			'host' => $connection?->host ?? '127.0.0.1',
			'port' => $connection?->port ?? 6379,
			'password' => $connection?->password ?? null,
		];
	}

	/**
	 * Generates a unique connection identifier.
	 *
	 * @return string
	 */
	protected function generateConnectionId(): string
	{
		return uniqid('sse_', true);
	}

	/**
	 * Identifies the current user. Falls back to "guest" when no session
	 * user exists. Used for both the per-endpoint singleton key and the
	 * user-wide close channel.
	 *
	 * @return string
	 */
	protected function getUserIdentifier(): string
	{
		return (string)($this->session->user->id ?? 'guest');
	}

	/**
	 * Gets the cache key for tracking the singleton-per-endpoint connection
	 * registration.
	 *
	 * @return string
	 */
	protected function getConnectionKey(): string
	{
		$userId = $this->getUserIdentifier();
		$sessionId = session_id();

		// Include URI path so different SSE endpoints (e.g.
		// /activity/sync vs /conversation/sync) don't kick each other off.
		$uri = Input::server('REQUEST_URI');
		$path = parse_url($uri, PHP_URL_PATH) ?? '';
		$pathHash = md5($path);

		return "sse:connection:{$userId}:{$sessionId}:{$pathHash}";
	}

	/**
	 * Per-connection close channel. A publish here interrupts the blocking
	 * subscribe and causes this single stream to exit cleanly.
	 *
	 * @param string $connectionId
	 * @return string
	 */
	protected function getCloseChannel(string $connectionId): string
	{
		return "sse:close:{$connectionId}";
	}

	/**
	 * User-wide close channel. A publish here closes every active SSE
	 * stream owned by this user across all endpoints. Useful for logout,
	 * permission revocation, or admin "kick" actions.
	 *
	 * @param string $userId
	 * @return string
	 */
	protected function getUserCloseChannel(string $userId): string
	{
		return "sse:user:close:{$userId}";
	}

	/**
	 * Closes stale per-endpoint connections for the same user/session.
	 * Triggered when the same user opens the same SSE endpoint again
	 * (e.g. page refresh).
	 *
	 * @return void
	 */
	protected function closeStaleConnections(): void
	{
		$key = $this->getConnectionKey();
		$oldConnectionId = Cache::get($key);

		if ($oldConnectionId && $oldConnectionId !== $this->connectionId)
		{
			Cache::set("sse:close:{$oldConnectionId}", '1', 5);
			$this->publishCloseSignal($this->getCloseChannel($oldConnectionId));

			error_log("SSE: Signaling stale connection {$oldConnectionId} to close");
		}
	}

	/**
	 * Publishes a close signal to the given channel. Uses a separate Redis
	 * connection because the main one is reserved for subscribing.
	 *
	 * @param string $channel Fully-qualified channel name to publish to.
	 * @return void
	 */
	protected function publishCloseSignal(string $channel): void
	{
		try
		{
			/**
			 * @SuppressWarnings PHP0413
			 */
			$publisher = new \Redis();
			$publisher->connect($this->settings['host'], $this->settings['port']);

			if (!empty($this->settings['password']))
			{
				$publisher->auth($this->settings['password']);
			}

			$publisher->publish($channel, 'close');
			$publisher->close();
		}
		catch (\Throwable $e)
		{
			error_log("SSE: Failed to publish close signal to {$channel}: " . $e->getMessage());
		}
	}

	/**
	 * Closes every active SSE stream for the given user across all
	 * endpoints. Safe to call from any normal HTTP request (e.g. logout
	 * handler) — does NOT initialise any SSE response headers.
	 *
	 * @param int|string $userId
	 * @param array|null $settings Optional Redis connection settings.
	 * @return void
	 */
	public static function closeUserConnections(int|string $userId, ?array $settings = null): void
	{
		$channel = "sse:user:close:{$userId}";

		try
		{
			$cfg = $settings ?? (function (): array
			{
				$connection = env('cache')?->connection ?? null;
				return [
					'host' => $connection?->host ?? '127.0.0.1',
					'port' => $connection?->port ?? 6379,
					'password' => $connection?->password ?? null,
				];
			})();

			/**
			 * @SuppressWarnings PHP0413
			 */
			$publisher = new \Redis();
			$publisher->connect($cfg['host'], $cfg['port']);

			if (!empty($cfg['password']))
			{
				$publisher->auth($cfg['password']);
			}

			$publisher->publish($channel, 'close');
			$publisher->close();
		}
		catch (\Throwable $e)
		{
			error_log("SSE: closeUserConnections failed for {$userId}: " . $e->getMessage());
		}
	}

	/**
	 * Registers this connection in the cache.
	 *
	 * @return void
	 */
	protected function registerConnection(): void
	{
		// TTL is max-duration + a generous grace so stale entries
		// self-evict if cleanup ever fails.
		$ttl = $this->sseConfig->scriptTimeLimit() + 60;
		Cache::set($this->getConnectionKey(), $this->connectionId, $ttl);
	}

	/**
	 * Checks if this connection should close (signaled via cache fallback).
	 *
	 * @return bool
	 */
	protected function shouldClose(): bool
	{
		$closeSignal = Cache::get("sse:close:{$this->connectionId}");
		return $closeSignal !== null;
	}

	/**
	 * Connects to Redis.
	 *
	 * @return void
	 */
	protected function connectToRedis(): void
	{
		/**
		 * @SuppressWarnings PHP0413
		 */
		$this->connection = new \Redis();

		if (!$this->connection->connect($this->settings['host'], $this->settings['port']))
		{
			throw new \RuntimeException('Failed to connect to Redis server.');
		}

		if (!empty($this->settings['password']) && !$this->connection->auth($this->settings['password']))
		{
			throw new \RuntimeException('Redis authentication failed.');
		}

		// Cap the read timeout at heartbeat interval so subscribe() always
		// returns control quickly enough to run liveness/deadline checks.
		$readTimeout = min(
			$this->sseConfig->redisReadTimeout,
			$this->sseConfig->heartbeatInterval
		);
		$this->connection->setOption(\Redis::OPT_READ_TIMEOUT, $readTimeout);
	}

	/**
	 * Subscribe to Redis channels and stream messages to the client.
	 *
	 * @param array|string $channels The channel(s) to subscribe to.
	 * @param callable|null $callback Optional callback to process messages.
	 * @return void
	 */
	public function subscribe(array|string $channels, ?callable $callback = null): void
	{
		$channels = is_array($channels) ? $channels : [$channels];
		$reconnectFailures = 0;

		// Always include the per-connection and user-wide close channels.
		$closeChannel = $this->getCloseChannel($this->connectionId);
		$userCloseChannel = $this->getUserCloseChannel($this->getUserIdentifier());
		$channels[] = $closeChannel;
		$channels[] = $userCloseChannel;

		if ($callback === null)
		{
			$callback = function($channel, $message)
			{
				return $message;
			};
		}

		while ($this->active)
		{
			if ($this->isOverDeadline())
			{
				error_log("SSE: Connection {$this->connectionId} reached max duration; closing");
				break;
			}

			if ($this->shouldClose())
			{
				error_log("SSE: Connection {$this->connectionId} closed by signal");
				break;
			}

			try
			{
				$reconnectFailures = 0;

				$this->connection->subscribe($channels, function($redis, $channel, $message) use ($callback, $closeChannel, $userCloseChannel)
				{
					if ($channel === $closeChannel || $channel === $userCloseChannel)
					{
						error_log("SSE: Connection {$this->connectionId} received close signal on {$channel}");
						$this->active = false;
						$redis->close();
						return;
					}

					if ($this->shouldClose() || $this->isOverDeadline())
					{
						$this->active = false;
						$redis->close();
						return;
					}

					$payload = json_decode($message, true) ?? $message;
					$result = $callback($channel, $payload);

					if ($result === false)
					{
						$this->active = false;
						$redis->close();
						return;
					}

					if ($result !== null)
					{
						if (!$this->sendMessage($result))
						{
							// Client is gone — bail.
							$this->active = false;
							$redis->close();
							return;
						}
					}
				});
			}
			catch (\RedisException $e)
			{
				if (strpos($e->getMessage(), 'read error') !== false)
				{
					// Read timeout — heartbeat the client. If that write
					// fails (broken pipe), the client is gone and we exit.
					if (!$this->sendHeartbeat())
					{
						error_log("SSE: Heartbeat write failed for {$this->connectionId}; client gone");
						break;
					}

					if (!$this->reconnectToRedis())
					{
						$reconnectFailures++;
						if ($reconnectFailures >= $this->sseConfig->maxReconnectFailures)
						{
							error_log("SSE: Max reconnect failures reached, closing connection");
							break;
						}
						usleep(100000);
					}

					continue;
				}

				error_log("SSE: Redis error: " . $e->getMessage());
				break;
			}
			catch (\Throwable $e)
			{
				error_log("SSE: Unexpected error: " . $e->getMessage());
				break;
			}
		}

		$this->close();
	}

	/**
	 * Sends a message to the client. Returns false if the underlying write
	 * fails (broken pipe).
	 *
	 * @param mixed $data
	 * @return bool
	 */
	protected function sendMessage(mixed $data): bool
	{
		$json = json_encode($data);
		if ($json === false)
		{
			return true;
		}

		return $this->response->writeEvent($json, 'message');
	}

	/**
	 * Sends a heartbeat comment to detect client disconnection. Uses
	 * `fwrite()` rather than `echo` so a broken pipe is reported as a
	 * write failure instead of silently succeeding.
	 *
	 * @return bool True if heartbeat was delivered.
	 */
	protected function sendHeartbeat(): bool
	{
		return StreamWriter::writeAndFlush(": heartbeat\n\n") && StreamWriter::isAlive();
	}

	/**
	 * Reconnects to Redis after a read timeout.
	 *
	 * @return bool
	 */
	protected function reconnectToRedis(): bool
	{
		try
		{
			$this->connection->close();
		}
		catch (\Throwable $e)
		{
			// Ignore close errors
		}

		try
		{
			$this->connectToRedis();
			return true;
		}
		catch (\Throwable $e)
		{
			error_log("SSE: Redis reconnection failed: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Closes the Redis connection and cleans up registry. Idempotent.
	 *
	 * @return void
	 */
	protected function close(): void
	{
		try
		{
			if ($this->active)
			{
				$this->active = false;

				try
				{
					$this->connection->close();
				}
				catch (\Throwable $e)
				{
					// Ignore — connection may already be torn down.
				}
			}

			$key = $this->getConnectionKey();
			$currentId = Cache::get($key);

			if ($currentId === $this->connectionId)
			{
				Cache::delete($key);
			}

			Cache::delete("sse:close:{$this->connectionId}");
		}
		catch (\Throwable $e)
		{
			// Ignore errors during cleanup.
		}
	}

	/**
	 * Gets session data by key using Proto's session system.
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected function getSessionData(string $key): mixed
	{
		return $this->session->{$key} ?? null;
	}

	/**
	 * Destructor — runs when the script exits cleanly. The shutdown handler
	 * registered in the constructor handles the abnormal-exit case.
	 */
	public function __destruct()
	{
		$this->close();
	}
}
