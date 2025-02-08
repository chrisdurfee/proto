<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Http\Session\DatabaseSession;
use Proto\Http\Session\FileSession;
use Proto\Http\Session\SessionInterface;
use Proto\Config;

/**
 * Class Session
 *
 * Manages session handling using different storage adapters (file or database).
 *
 * @package Proto\Http
 */
class Session
{
	/**
	 * The session instance.
	 */
	protected static ?SessionInterface $instance = null;

	/**
	 * The session type (file or database).
	 */
	protected static ?string $type = null;

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct()
	{
	}

	/**
	 * Retrieves the session type from configuration.
	 *
	 * @return string
	 */
	protected static function getConfigType(): string
	{
		$config = Config::getInstance();
		return ($config->session ?? 'file') === 'file' ? FileSession::class : DatabaseSession::class;
	}

	/**
	 * Sets the session type.
	 *
	 * @return string
	 */
	protected static function setType(): string
	{
		return self::$type ??= self::getConfigType();
	}

	/**
	 * Retrieves the session type.
	 *
	 * @return string
	 */
	protected static function getType(): string
	{
		return self::setType();
	}

	/**
	 * Retrieves the session instance.
	 *
	 * @return SessionInterface
	 */
	public static function getInstance(): SessionInterface
	{
		$type = self::getType();
		return self::$instance ??= $type::getInstance();
	}

	/**
	 * Initializes the session and closes it to prevent session locking.
	 *
	 * @return SessionInterface
	 */
	public static function init(): SessionInterface
	{
		$type = self::getType();
		return $type::init();
	}

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		return static::getInstance()->getId();
	}

	/**
	 * Retrieves a session value by key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get(string $key): mixed
	{
		return static::getInstance()->{$key} ?? null;
	}

	/**
	 * Destroys the current session.
	 *
	 * @return void
	 */
	public static function destroy(): void
	{
		static::getInstance()->destroy();
	}
}