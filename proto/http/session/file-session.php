<?php declare(strict_types=1);
namespace Proto\Http\Session;

/**
 * FileSession
 *
 * Handles file-based session management.
 *
 * @package Proto\Http\Session
 */
class FileSession extends Adapter
{
	/**
	 * Indicates if the session is started.
	 *
	 * @var bool $started
	 */
	protected bool $started = false;

	/**
	 * Indicates if the session is opened.
	 *
	 * @var bool $opened
	 */
	protected bool $opened = false;

	/**
	 * Starts the session and closes it to prevent session locking.
	 *
	 * @return FileSession
	 */
	public static function init(): FileSession
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
	 * Checks if the session is started.
	 *
	 * @return bool
	 */
	public function isStarted(): bool
	{
		return ($this->started === true);
	}

	/**
	 * Starts the session.
	 *
	 * @return bool
	 */
	public function start(): bool
	{
		if ($this->isStarted())
		{
			return false;
		}

		$this->opened = true;
		$this->started = true;
		if (session_status() == PHP_SESSION_NONE)
		{
			return session_start();
		}
		return true;
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
			$this->opened = true;
			ini_set('session.use_only_cookies', '0');
			ini_set('session.use_cookies', '0');
			ini_set('session.use_trans_sid', '0');
			ini_set('session.cache_limiter', '');
			session_start();
		}
	}

	/**
	 * Closes the session to stop session locking.
	 *
	 * @return void
	 */
	public function close(): void
	{
		if ($this->opened === true)
		{
			$this->opened = false;
			session_write_close();
		}
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
		$this->open();
		$_SESSION[$key] = $value;
		$this->close();
	}

	/**
	 * Retrieves a session value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $_SESSION[$key] ?? null;
	}

	/**
	 * Unsets a session value.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		unset($_SESSION[$key]);
	}

	/**
	 * Destroys the session.
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
		$result = session_destroy();
		unset($_SESSION);

		$this->close();
		return $result;
	}
}