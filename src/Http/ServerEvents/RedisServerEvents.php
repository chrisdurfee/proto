<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

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
	 * Constructor.
	 *
	 * @param array|null $settings Optional Redis connection settings.
	 */
	public function __construct(?array $settings = null)
	{
		$this->settings = $settings ?? $this->getDefaultSettings();
		$this->configureStreaming();
		$this->setupResponse();
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

		// Default callback just returns the message
		if ($callback === null)
		{
			$callback = function($channel, $message)
			{
				return $message;
			};
		}

		try
		{
			$this->connection->subscribe($channels, function($redis, $channel, $message) use ($callback)
			{
				// Check if client disconnected
				if (connection_aborted())
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
		catch (\Throwable $e)
		{
			$this->close();
		}
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
	 * Closes the Redis connection.
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
