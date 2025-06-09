<?php declare(strict_types=1);
namespace Proto\Http\Session;

/**
 * FileSession
 *
 * Manages file-based PHP sessions securely and efficiently.
 *
 * @package Proto\Http\Session
 */
class FileSession extends Adapter
{
	/**
	 * Indicates if the session is started.
	 *
	 * @var bool
	 */
	protected bool $started = false;

	/**
	 * Indicates if the session is opened.
	 *
	 * @var bool
	 */
	protected bool $opened = false;

	/**
	 * Initializes the session and closes it to prevent locking.
	 *
	 * @return static
	 */
	public static function init(): static
	{
		$instance = static::getInstance();
		$instance->start();
		$instance->close();
		return $instance;
	}

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		return session_id();
	}

	/**
	 * Refreshes the session ID for security.
	 *
	 * @return string
	 */
	public function refreshId(): string
	{
		if (session_status() === PHP_SESSION_ACTIVE)
		{
			session_regenerate_id(true);
		}
		return session_id();
	}

	/**
	 * Checks if the session is started.
	 *
	 * @return bool
	 */
	public function isStarted(): bool
	{
		return $this->started;
	}

	/**
	 * Starts the session securely.
	 *
	 * @return bool
	 */
	public function start(): bool
	{
		if ($this->isStarted() || session_status() !== PHP_SESSION_NONE)
		{
			return false;
		}

		$this->configureSession();
		$this->opened = true;
		$this->started = session_start();

		return $this->started;
	}

	/**
	 * Opens the session to allow write access.
	 *
	 * @return void
	 */
	public function open(): void
	{
		if (!$this->opened)
		{
			$this->configureSession();
			$this->opened = session_status() === PHP_SESSION_ACTIVE || session_start();
		}
	}

	/**
	 * Configures session settings.
	 *
	 * @return void
	 */
	protected function configureSession(): void
	{
		ini_set('session.use_only_cookies', '1');
		ini_set('session.use_cookies', '1');
		ini_set('session.use_trans_sid', '0');
		ini_set('session.cache_limiter', 'nocache');
	}

	/**
	 * Closes the session to prevent session locking.
	 *
	 * @return void
	 */
	public function close(): void
	{
		if ($this->opened)
		{
			session_write_close();
			$this->opened = false;
		}
	}

	/**
	 * Sets a session variable.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->open();
		$_SESSION[$key] = $value;
		$this->close();
	}

	/**
	 * Retrieves a session variable.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		$this->open();
		return $_SESSION[$key] ?? null;
	}

	/**
	 * Checks if a session variable is set.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool
	{
		$this->open();
		return isset($_SESSION[$key]);
	}

	/**
	 * Unsets a session variable.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		$this->open();
		unset($_SESSION[$key]);
		$this->close();
	}

	/**
	 * Destroys the session and removes session data.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		if (!$this->isStarted())
		{
			return false;
		}

		$this->open();
		$this->started = false;

		$_SESSION = [];
		session_unset();

		$result = session_destroy();

		$this->close();
		return $result;
	}
}