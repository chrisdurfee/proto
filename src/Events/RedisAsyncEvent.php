<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Http\Loop\Event;
use Proto\Http\Loop\AsyncEventInterface;

/**
 * RedisAsyncEvent
 *
 * Integrates Redis pub/sub with the EventLoop for asynchronous event handling.
 * Ideal for Server-Sent Events (SSE) and real-time streaming applications.
 *
 * This class uses a dedicated Redis connection for non-blocking pub/sub operations.
 * The reason for a separate connection is that Redis SUBSCRIBE is a blocking operation
 * that puts the connection into "pub/sub mode" where it can't execute other commands.
 * This prevents the main cache driver from being blocked by long-running subscriptions.
 *
 * Works like UpdateEvent: return values from the callback are sent as SSE messages,
 * return false to terminate the event.
 *
 * @package Proto\Events
 */
class RedisAsyncEvent extends Event implements AsyncEventInterface
{
	/**
	 * @var \Redis Dedicated Redis connection for pub/sub operations.
	 * @SuppressWarnings PHP0413
	 */
	protected \Redis $connection;

	/**
	 * @var array The channels to subscribe to.
	 */
	protected array $channels;

	/**
	 * @var callable The callback to execute when a message is received.
	 */
	protected $callback;

	/**
	 * @var bool Whether the subscription is active.
	 */
	protected bool $subscribed = false;

	/**
	 * @var bool Whether the event has been terminated.
	 */
	protected bool $terminated = false;

	/**
	 * @var array Connection settings.
	 */
	protected array $settings;

	/**
	 * @var bool Whether the initial subscription has been set up.
	 */
	protected bool $initialSubscribed = false;

	/**
	 * Constructor.
	 *
	 * @param array|string $channels The channel(s) to subscribe to (without redis: prefix).
	 * @param callable $callback The callback to execute when a message is received.
	 * Receives ($channel, $message, $event) as parameters.
	 * Return a value to send as SSE message, false to terminate.
	 * @param array|null $settings Optional Redis connection settings (host, port, password).
	 */
	public function __construct(array|string $channels, callable $callback, ?array $settings = null)
	{
		parent::__construct();
		$this->channels = is_array($channels) ? $channels : [$channels];
		$this->callback = $callback;
		$this->settings = $settings ?? $this->getDefaultSettings();
		$this->initialize();
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
	 * Initializes a dedicated Redis connection for pub/sub.
	 *
	 * @return void
	 */
	protected function initialize(): void
	{
		try
		{
			/**
			 * @SuppressWarnings PHP0413
			 */
			$this->connection = new \Redis();

			// Connect to Redis (use connect instead of pconnect for pub/sub)
			if (!$this->connection->connect($this->settings['host'], $this->settings['port']))
			{
				throw new \RuntimeException('Failed to connect to Redis server.');
			}

			// Authenticate if password is set
			if (!empty($this->settings['password']) && !$this->connection->auth($this->settings['password']))
			{
				throw new \RuntimeException('Redis authentication failed.');
			}

			$this->subscribed = true;
		}
		catch (\Exception $e)
		{
			$this->terminated = true;
			error_log("RedisAsyncEvent initialization failed: " . $e->getMessage());
		}
	}

	/**
	 * Defines the logic to be executed when the event is created.
	 * Starts the Redis subscription in a non-blocking way.
	 */
	protected function run(): void
	{
		if (!$this->subscribed)
		{
			return;
		}

		// The subscription will be set up on the first tick()
		// to avoid blocking during initialization
	}

	/**
	 * Processes Redis pub/sub messages on each event loop tick.
	 * Checks for new messages and processes the callback.
	 * Return values from callback are sent as SSE messages.
	 *
	 * @return void
	 */
	public function tick(): void
	{
		if ($this->terminated || !$this->subscribed)
		{
			return;
		}

		// Subscribe once and stay in the subscription loop
		// The subscribe() call will block and process messages as they arrive
		if (!$this->initialSubscribed)
		{
			$this->initialSubscribed = true;
			$this->startSubscription();
		}
	}

	/**
	 * Starts the Redis subscription and processes messages.
	 * This method blocks while listening for messages.
	 *
	 * @return void
	 */
	protected function startSubscription(): void
	{
		try
		{
			// The subscribe() call blocks and processes messages via the callback
			// It will continue running until unsubscribed or connection is closed
			$this->connection->subscribe($this->channels, function ($redis, $channel, $message) {
				// Check if connection is still alive
				if (connection_aborted())
				{
					$this->terminate();
					$redis->unsubscribe();
					return;
				}

				// Decode JSON if applicable
				$payload = json_decode($message, true) ?? $message;

				// Call the user callback with channel, message, and event instance
				$result = ($this->callback)($channel, $payload, $this);

				// If callback returns false, terminate the event
				if ($result === false)
				{
					$this->terminate();
					$redis->unsubscribe();
					return;
				}

				// If callback returns a value, send it as SSE message
				if ($result !== null)
				{
					$this->message($result);
				}
			});
		}
		catch (\RedisException $e)
		{
			$this->terminate();
		}
		catch (\Exception $e)
		{
			$this->terminate();
		}
	}

	/**
	 * Checks if the event has been terminated.
	 *
	 * @return bool
	 */
	public function isTerminated(): bool
	{
		return $this->terminated;
	}

	/**
	 * Terminates the event and unsubscribes from Redis channels.
	 *
	 * @return void
	 */
	public function terminate(): void
	{
		if ($this->terminated)
		{
			return;
		}

		$this->terminated = true;

		try
		{
			if ($this->subscribed)
			{
				$this->connection->unsubscribe($this->channels);
				$this->connection->close();
			}
		}
		catch (\Exception $e)
		{
			// Ignore errors during cleanup
		}

		$this->subscribed = false;
	}

	/**
	 * Gets whether the subscription is active.
	 *
	 * @return bool
	 */
	public function isSubscribed(): bool
	{
		return $this->subscribed;
	}

	/**
	 * Gets the subscribed channels.
	 *
	 * @return array
	 */
	public function getChannels(): array
	{
		return $this->channels;
	}

	/**
	 * Factory method to create a RedisAsyncEvent using the default Redis cache connection.
	 * Strips 'redis:' prefix from channels if present for convenience.
	 *
	 * @param array|string $channels Channel(s) to subscribe to.
	 * @param callable $callback The callback to execute.
	 * @return self
	 */
	public static function create(array|string $channels, callable $callback): self
	{
		// Strip 'redis:' prefix if present
		$channels = is_array($channels) ? $channels : [$channels];
		$channels = array_map(function($channel) {
			return str_starts_with($channel, 'redis:')
				? substr($channel, 6)
				: $channel;
		}, $channels);

		return new self($channels, $callback);
	}
}
