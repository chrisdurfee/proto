<?php declare(strict_types=1);
namespace Proto\Patterns\Structural;

/**
 * PubSub
 *
 * A structural design pattern that provides a publish/subscribe
 * mechanism for communication between objects, enabling loosely
 * coupled and scalable systems.
 *
 * @package Proto\Patterns\Structural
 */
class PubSub
{
	/**
	 * Holds the previous token ID.
	 *
	 * @var int
	 */
	protected int $previousTokenId = 0;

	/**
	 * Holds the subscribers.
	 *
	 * @var array
	 */
	protected array $subscribers = [];

	/**
	 * Retrieves the subscribers by key.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @return array Returns an array of subscribers, or an empty array if none found.
	 */
	public function getSubscribers(string $key): array
	{
		return $this->subscribers[$key] ?? [];
	}

	/**
	 * Ensures the given key has a subscriber list initialized.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @return void
	 */
	protected function setupSubscribers(string $key): void
	{
		if (!isset($this->subscribers[$key]))
		{
			$this->subscribers[$key] = [];
		}
	}

	/**
	 * Generates a unique subscription token.
	 *
	 * @return string The generated token.
	 */
	protected function getToken(): string
	{
		return 'id-' . (++$this->previousTokenId);
	}

	/**
	 * Subscribes a callback to a key.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @param callable $callback The callback function to be executed.
	 * @return string The subscription token.
	 */
	public function subscribe(string $key, callable $callback): string
	{
		$this->setupSubscribers($key);

		$token = $this->getToken();
		$this->subscribers[$key][$token] = $callback;

		return $token;
	}

	/**
	 * Unsubscribes a callback from a key using its token.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @param string $token The subscription token.
	 * @return void
	 */
	public function unsubscribe(string $key, string $token): void
	{
		if (isset($this->subscribers[$key][$token]))
		{
			unset($this->subscribers[$key][$token]);
		}
	}

	/**
	 * Publishes a message to all subscribers of a given key.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @param mixed $message The message to be published.
	 * @return void
	 */
	public function publish(string $key, mixed $message): void
	{
		foreach ($this->getSubscribers($key) as $subscriber)
		{
			$subscriber($message);
		}
	}

	/**
	 * Clears all subscribers for a given key.
	 *
	 * @param string $key The key identifying the group of subscribers.
	 * @return void
	 */
	public function clearSubscribers(string $key): void
	{
		unset($this->subscribers[$key]);
	}

	/**
	 * Clears all subscribers for all keys.
	 *
	 * @return void
	 */
	public function clearAll(): void
	{
		$this->subscribers = [];
	}
}