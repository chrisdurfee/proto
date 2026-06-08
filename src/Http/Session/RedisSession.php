<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Cache\Cache;
use Proto\Http\Token;
use Proto\Utils\Format\JsonFormat;

/**
 * RedisSession
 *
 * Handles Redis-backed session management. Session data is stored in the
 * configured cache driver (Redis) keyed by a secure cookie token, enabling
 * shared session state across multiple application instances.
 *
 * Requires a configured cache driver. When no driver is available, the
 * Session manager falls back to file-based sessions.
 *
 * @package Proto\Http\Session
 */
class RedisSession extends Adapter
{
	/**
	 * Session token.
	 *
	 * @var string|null
	 */
	protected static ?string $token = null;

	/**
	 * Session data.
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * Session lifetime in seconds.
	 *
	 * @var int
	 */
	protected int $lifetime = 86400;

	/**
	 * Key prefix for session entries in the cache.
	 *
	 * @var string
	 */
	protected const KEY_PREFIX = 'session:';

	/**
	 * Initializes and starts a new session or resumes an existing one.
	 *
	 * @return static
	 */
	public static function init(): static
	{
		$instance = static::getInstance();
		$instance->start();
		return $instance;
	}

	/**
	 * Retrieves or generates the session token.
	 *
	 * @return string
	 */
	protected function getToken(): string
	{
		$cookie = Token::get();
		return $cookie ? $cookie : Token::create();
	}

	/**
	 * Sets up the session token.
	 *
	 * @return void
	 */
	protected function setupToken(): void
	{
		if (static::$token === null)
		{
			static::$token = $this->getToken();
		}
	}

	/**
	 * Sets up the session lifetime from configuration.
	 *
	 * @return void
	 */
	protected function setupLifetime(): void
	{
		$lifetime = env('sessionLifetime');
		if (is_int($lifetime) && $lifetime > 0)
		{
			$this->lifetime = $lifetime;
		}
	}

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		return static::$token ?? '';
	}

	/**
	 * Builds the cache key for a session token.
	 *
	 * @param string|null $token Optional token; defaults to the current token.
	 * @return string
	 */
	protected function cacheKey(?string $token = null): string
	{
		return self::KEY_PREFIX . ($token ?? static::$token ?? '');
	}

	/**
	 * Refreshes the session ID for security, migrating data to the new key.
	 *
	 * @return string
	 */
	public function refreshId(): string
	{
		$old = static::$token ?? $this->getToken();
		$new = Token::create();
		static::$token = $new;

		$encoded = JsonFormat::encode($this->data);
		if ($encoded !== false)
		{
			Cache::set($this->cacheKey($new), $encoded, $this->lifetime);
		}
		Cache::delete($this->cacheKey($old));

		return $new;
	}

	/**
	 * Loads session data from the cache.
	 *
	 * @return void
	 */
	protected function loadData(): void
	{
		$raw = Cache::get($this->cacheKey());
		if ($raw !== null)
		{
			$this->data = (array)JsonFormat::decode($raw) ?: [];
		}
	}

	/**
	 * Saves the session data to the cache.
	 *
	 * @return bool
	 */
	protected function saveData(): bool
	{
		$encoded = JsonFormat::encode($this->data);
		if ($encoded === false)
		{
			return false;
		}

		Cache::set($this->cacheKey(), $encoded, $this->lifetime);
		return true;
	}

	/**
	 * Starts the session.
	 *
	 * @return void
	 * @throws \RuntimeException When no cache driver is available.
	 */
	public function start(): void
	{
		if (static::$token !== null)
		{
			return;
		}

		if (Cache::driver() === null)
		{
			throw new \RuntimeException('A cache driver is required for RedisSession.');
		}

		$this->setupLifetime();
		$this->setupToken();
		$this->loadData();
	}

	/**
	 * Sets a session value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
		$this->saveData();
	}

	/**
	 * Gets a session value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	/**
	 * Checks if a session key exists.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool
	{
		return isset($this->data[$key]);
	}

	/**
	 * Unsets a session value.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		unset($this->data[$key]);
		$this->saveData();
	}

	/**
	 * Destroys the session.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		Token::remove();

		$result = Cache::delete($this->cacheKey());
		$this->data = [];
		static::$token = null;

		return $result;
	}
}
