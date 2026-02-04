<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Cache\Cache;

/**
 * RedisServerEvents
 *
 * Implements Server-Sent Events (SSE) directly with Redis pub/sub.
 * Does not use EventLoop since Redis subscribe() is already blocking.
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
	 * @var int Read timeout in seconds for Redis connection.
	 * Higher values reduce reconnection frequency but delay disconnect detection.
	 */
	protected int $readTimeout = 30;

	/**
	 * @var int Maximum consecutive reconnection failures before giving up.
	 */
	protected int $maxReconnectFailures = 3;

	/**
	 * Constructor.
	 *
	 * @param array|null $settings Optional Redis connection settings.
	 */
	public function __construct(?array $settings = null)
	{
		$this->settings = $settings ?? $this->getDefaultSettings();
		$this->connectionId = $this->generateConnectionId();
		$this->configureStreaming();
		$this->setupResponse();
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
	 * Gets the cache key for tracking connections by user/session.
	 *
	 * @return string
	 */
	protected function getConnectionKey(): string
	{
		$userId = $this->session->user->id ?? 'guest';
		$sessionId = session_id();
		return "sse:connection:{$userId}:{$sessionId}";
	}

	/**
	 * Closes stale connections for the same user/session.
	 * When a user refreshes the page, this signals the old connection to close
	 * immediately instead of waiting for timeout.
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
			error_log("SSE: Closing stale connection {$oldConnectionId}");
		}
	}

	/**
	 * Registers this connection in the cache.
	 *
	 * @return void
	 */
	protected function registerConnection(): void
	{
		Cache::set($this->getConnectionKey(), $this->connectionId, 300);
	}

	/**
	 * Checks if this connection should close (signaled by newer connection).
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

		// Set a read timeout so subscribe() periodically returns, allowing us
		// to check if the client is still connected. Without this, subscribe()
		// blocks indefinitely and doesn't detect client disconnection until
		// a message arrives, causing requests to hang on page refresh.
		$this->connection->setOption(\Redis::OPT_READ_TIMEOUT, $this->readTimeout);
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

		// Default callback just returns the message
		if ($callback === null)
		{
			$callback = function($channel, $message)
			{
				return $message;
			};
		}

		// Loop to handle Redis read timeouts and check for client disconnection.
		// When read timeout expires, subscribe() throws an exception. We catch it,
		// check if client is still connected, and re-subscribe if so.
		while ($this->active && !connection_aborted())
		{
			// Check if newer connection signaled us to close
			if ($this->shouldClose())
			{
				error_log("SSE: Connection {$this->connectionId} closed by newer connection");
				break;
			}

			try
			{
				// Reset failure count on successful subscribe entry
				$reconnectFailures = 0;

				$this->connection->subscribe($channels, function($redis, $channel, $message) use ($callback)
				{
					// Check if client disconnected or signaled to close
					if (connection_aborted() || $this->shouldClose())
					{
						$this->active = false;
						return;
					}

					// Decode JSON if applicable
					$payload = json_decode($message, true) ?? $message;

					// Call the user callback
					$result = $callback($channel, $payload);

					// If callback returns false, stop subscription
					if ($result === false)
					{
						$this->active = false;
						return;
					}

					// Send result as SSE message
					if ($result !== null)
					{
						$this->sendMessage($result);
					}
				});
			}
			catch (\RedisException $e)
			{
				// Read timeout - this is expected. Send a heartbeat comment to
				// detect client disconnection (output will fail if client gone).
				if (strpos($e->getMessage(), 'read error') !== false)
				{
					// Check client connection before attempting heartbeat
					if (connection_aborted())
					{
						break;
					}

					// Send heartbeat - if this fails, client is disconnected
					if (!$this->sendHeartbeat())
					{
						break;
					}

					// Attempt to reconnect to Redis
					if (!$this->reconnectToRedis())
					{
						$reconnectFailures++;
						if ($reconnectFailures >= $this->maxReconnectFailures)
						{
							error_log("SSE: Max reconnect failures reached, closing connection");
							break;
						}

						// Brief delay before retry
						usleep(100000); // 100ms
					}

					continue;
				}

				// Other Redis error - exit the loop
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
	 * Sends a message to the client.
	 *
	 * @param mixed $data The data to send.
	 * @return void
	 */
	protected function sendMessage(mixed $data): void
	{
		$json = json_encode($data);
		$this->response->sendEvent($json, 'message');
	}

	/**
	 * Sends a heartbeat comment to detect client disconnection.
	 * SSE comments (lines starting with :) are ignored by the client
	 * but will cause output to fail if the client has disconnected.
	 *
	 * @return bool True if heartbeat was sent successfully, false if client disconnected.
	 */
	protected function sendHeartbeat(): bool
	{
		echo ": heartbeat\n\n";
		if (ob_get_level() > 0)
		{
			ob_flush();
		}
		flush();

		// Check if client disconnected during output
		return !connection_aborted();
	}

	/**
	 * Reconnects to Redis after a read timeout.
	 * After a timeout, the subscribe connection is broken and must be re-established.
	 *
	 * @return bool True if reconnection succeeded, false otherwise.
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
	 * Closes the Redis connection and cleans up connection registry.
	 *
	 * @return void
	 */
	protected function close(): void
	{
		try
		{
			if ($this->active)
			{
				$this->connection->close();
			}

			// Clean up connection registry
			$key = $this->getConnectionKey();
			$currentId = Cache::get($key);

			// Only remove if we're still the registered connection
			if ($currentId === $this->connectionId)
			{
				Cache::delete($key);
			}

			// Clean up close signal
			Cache::delete("sse:close:{$this->connectionId}");
		}
		catch (\Throwable $e)
		{
			// Ignore errors during cleanup
		}
		$this->active = false;
	}

	/**
	 * Gets session data by key using Proto's session system.
	 * Safe to call during SSE stream (automatically handles session locking).
	 *
	 * @param string $key Session key.
	 * @return mixed
	 */
	protected function getSessionData(string $key): mixed
	{
		// Proto's session system automatically opens/closes to prevent blocking
		return $this->session->{$key} ?? null;
	}

	/**
	 * Destructor - ensures cleanup.
	 */
	public function __destruct()
	{
		$this->close();
	}
}
