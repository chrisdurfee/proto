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
	 * Since we use pub/sub close signals for immediate termination, this can be higher.
	 */
	protected int $readTimeout = 2;

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
	 * Gets the cache key for tracking connections by user/session/endpoint.
	 * Includes the request URI to prevent different SSE endpoints from conflicting.
	 *
	 * @return string
	 */
	protected function getConnectionKey(): string
	{
		$userId = $this->session->user->id ?? 'guest';
		$sessionId = session_id();

		// Include URI path to make key unique per endpoint
		// This prevents different SSE endpoints (e.g., /activity/sync vs /conversation/sync)
		// from signaling each other to close
		$uri = Input::server('REQUEST_URI');
		$path = parse_url($uri, PHP_URL_PATH) ?? '';
		$pathHash = md5($path);

		return "sse:connection:{$userId}:{$sessionId}:{$pathHash}";
	}

	/**
	 * Gets the close channel name for a specific connection.
	 * This channel is used to send immediate close signals via pub/sub.
	 *
	 * @param string $connectionId The connection ID.
	 * @return string
	 */
	protected function getCloseChannel(string $connectionId): string
	{
		return "sse:close:{$connectionId}";
	}

	/**
	 * Closes stale connections for the same user/session.
	 * When a user refreshes the page, this signals the old connection to close
	 * immediately by publishing to its close channel.
	 *
	 * @return void
	 */
	protected function closeStaleConnections(): void
	{
		$key = $this->getConnectionKey();
		$oldConnectionId = Cache::get($key);

		if ($oldConnectionId && $oldConnectionId !== $this->connectionId)
		{
			// Set cache flag as backup
			Cache::set("sse:close:{$oldConnectionId}", '1', 5);

			// Publish immediate close signal via Redis pub/sub
			$this->publishCloseSignal($oldConnectionId);

			error_log("SSE: Signaling stale connection {$oldConnectionId} to close");
		}
	}

	/**
	 * Publishes a close signal to a connection's close channel.
	 * This immediately interrupts the blocking subscribe.
	 *
	 * @param string $connectionId The connection ID to close.
	 * @return void
	 */
	protected function publishCloseSignal(string $connectionId): void
	{
		try
		{
			/**
			 * Use a separate Redis connection for publishing
			 * since the main connection will be used for subscribing.
			 * @SuppressWarnings PHP0413
			 */
			$publisher = new \Redis();
			$publisher->connect($this->settings['host'], $this->settings['port']);

			if (!empty($this->settings['password']))
			{
				$publisher->auth($this->settings['password']);
			}

			$publisher->publish($this->getCloseChannel($connectionId), 'close');
			$publisher->close();
		}
		catch (\Throwable $e)
		{
			// Ignore publish errors - cache fallback will work
			error_log("SSE: Failed to publish close signal: " . $e->getMessage());
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

		// Add the close channel so we can receive immediate close signals
		$closeChannel = $this->getCloseChannel($this->connectionId);
		$channels[] = $closeChannel;

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
		// send a heartbeat to verify client is still connected, and re-subscribe.
		// Note: We don't check connection_aborted() here as it's unreliable behind proxies.
		while ($this->active)
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

				$this->connection->subscribe($channels, function($redis, $channel, $message) use ($callback, $closeChannel)
				{
					// Check if this is a close signal
					if ($channel === $closeChannel)
					{
						error_log("SSE: Connection {$this->connectionId} received close signal");
						$this->active = false;
						return;
					}

					// Check if signaled to close by newer connection
					if ($this->shouldClose())
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
				// keep the connection alive and verify client is still connected.
				if (strpos($e->getMessage(), 'read error') !== false)
				{
					// Send heartbeat to keep connection alive
					$this->sendHeartbeat();

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
	 * Note: We don't rely on connection_aborted() as it's unreliable behind proxies.
	 * Instead, we assume success if no exception was thrown during output.
	 *
	 * @return bool True if heartbeat was sent successfully.
	 */
	protected function sendHeartbeat(): bool
	{
		try
		{
			echo ": heartbeat\n\n";

			if (ob_get_level() > 0)
			{
				ob_flush();
			}

			// Use @ to suppress warnings if connection is broken
			$result = @flush();

			// flush() returns false on failure in some PHP versions
			// but this isn't reliable, so we primarily rely on exceptions
			return true;
		}
		catch (\Throwable $e)
		{
			return false;
		}
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
