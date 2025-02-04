<?php declare(strict_types=1);
namespace Proto\Patterns\Structural;

/**
 * Class PubSub
 *
 * PubSub is a structural design pattern that provides a publish/subscribe
 * mechanism for communication between objects, which helps create
 * loosely coupled and scalable systems.
 *
 * @package Proto\Patterns\Structural
 */
class PubSub
{
	/**
	 * @var int $previousTokenId The ID of the previous token
	 */
	protected int $previousTokenId = 0;

	/**
	 * @var array $subscribers The subscribers array
	 */
	protected array $subscribers = [];

	/**
	 * Retrieves the subscribers by key.
	 *
	 * @param string $key The key identifying the group of subscribers
	 * @return array|null The array of subscribers or null if not found
	 */
	public function getSubscribers(string $key): ?array
	{
		return $this->subscribers[$key] ?? null;
	}

	/**
	 * Sets up the subscribers for a given key.
	 *
	 * @param string $key The key identifying the group of subscribers
	 * @return array|null The array of subscribers or null if not found
	 */
	protected function setupSubscribers(string $key): ?array
	{
		return ($this->getSubscribers($key) ?? ($this->subscribers[$key] = []));
	}

	/**
	 * Generates a unique token.
	 *
	 * @return string The generated token
	 */
	protected function getToken(): string
	{
		$tokenId = ++$this->previousTokenId;
		return 'id-' . $tokenId;
	}

	/**
	 * Adds a subscriber.
	 *
	 * @param string $key The key identifying the group of subscribers
	 * @param callable $callBack The callback function to be executed
	 * @return string The subscription token
	 */
	public function subscribe(string $key, callable $callBack): string
	{
		$this->setupSubscribers($key);

		$token = $this->getToken();
		$this->subscribers[$key][$token] = $callBack;
		return $token;
	}

	/**
	 * Removes a subscriber.
	 *
	 * @param string $key The key identifying the group of subscribers
	 * @param string $token The subscription token
	 * @return void
	 */
	public function unsubscribe(string $key, string $token): void
	{
		if (!isset($key) || !isset($token))
		{
			return;
		}

		$subscribers = $this->getSubscribers($key);
		if ($subscribers)
		{
			unset($this->subscribers[$key][$token]);
		}
	}

	/**
	 * Publishes the message to all subscribers.
	 *
	 * @param string $key The key identifying the group of subscribers
	 * @param mixed $message The message to be published
	 * @return void
	 */
	public function publish(string $key, mixed $message): void
	{
		$subscribers = $this->getSubscribers($key);
		if (!$subscribers)
		{
			return;
		}

		foreach ($subscribers as $subscriber)
		{
			call_user_func($subscriber, $message);
		}
	}
}