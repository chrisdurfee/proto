<?php declare(strict_types=1);
namespace Proto\Events;

use Proto\Http\Loop\Event;
use Proto\Http\Loop\AsyncEventInterface;

/**
 * RedisAsyncEvent (Safe Non-Blocking Version)
 *
 * Keeps a dedicated Redis connection open for the lifetime of an SSE stream.
 * Prevents premature closing by deferring cleanup until the client actually
 * disconnects. Uses a short read timeout to yield control back to the event loop.
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
	 * @var mixed The pubSubLoop iterator.
	 */
	protected $pubSubLoop = null;

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

		// Check if client connection is still alive
		if (connection_aborted())
		{
			$this->terminate();
			return;
		}

		// Set up pubSubLoop iterator once
		if (!$this->initialSubscribed)
		{
			$this->initialSubscribed = true;
			$this->setupSubscription();
		}

		// Process one message per tick
		if ($this->pubSubLoop !== null)
		{
			$this->processNextMessage();
		}
	}

	/**
	 * Sets up the Redis pubSubLoop iterator and subscribes to channels.
	 *
	 * @return void
	 */
	protected function setupSubscription(): void
	{
		try
		{
			$this->pubSubLoop = $this->connection->pubSubLoop();
			$this->pubSubLoop->subscribe(...$this->channels);
			$this->pubSubLoop->next(); // prime the iterator
		}
		catch (\Throwable $e)
		{
			$this->terminate();
		}
	}

	/**
	 * Processes the next message from the pubSubLoop iterator.
	 *
	 * @return void
	 */
	protected function processNextMessage(): void
	{
		try
		{
			// Get current message
			$message = $this->pubSubLoop->current();

			// Only process actual messages (not subscription confirmations)
			if ($message && ($message['type'] ?? null) === 'message')
			{
				$channel = $message['channel'];
				$rawPayload = $message['payload'];

				// Decode JSON if applicable
				$payload = json_decode($rawPayload, true) ?? $rawPayload;

				// Call the user callback with channel, message, and event instance
				$result = ($this->callback)($channel, $payload, $this);

				// If callback returns false, terminate the event
				if ($result === false)
				{
					$this->terminate();
					return;
				}

				// If callback returns a value, send it as SSE message
				if ($result !== null)
				{
					$this->message($result);
				}
			}

			// Advance to next message
			$this->pubSubLoop->next();
		}
		catch (\Throwable $e)
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
	 * Defers actual Redis cleanup until script shutdown to allow SSE to finish flushing.
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

		// Defer cleanup until script shutdown so SSE can finish flushing
		register_shutdown_function(function()
        {
			try
			{
				if ($this->pubSubLoop !== null)
				{
					$this->pubSubLoop->unsubscribe();
					$this->pubSubLoop = null;
				}
				if ($this->subscribed)
				{
					$this->connection->close();
				}
			}
			catch (\Throwable $e)
			{
				// Ignore errors during shutdown
			}
			$this->subscribed = false;
		});
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
