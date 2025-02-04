<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Http\Session\DatabaseSession;
use Proto\Http\Session\FileSession;
use Proto\Http\Session\SessionInterface;
use Proto\Config;

/**
 * Session
 *
 * This will handle the session.
 *
 * @package Proto\Http
 */
class Session
{
	/**
	 * @var object $instance
	 */
	protected static SessionInterface $instance;

	/**
	 * @var string $type
	 */
	protected static ?string $type;

	/**
	 * This will set the session as a singleton.
	 *
	 * @return void
	 */
	protected function __construct()
	{
	}

	/**
	 * This will get the config session type.
	 *
	 * @return string
	 */
	protected static function getConfigType(): string
	{
		$config = Config::getInstance();
		$session = $config->session ?? 'file';
		return ($session === 'file')? FileSession::class : DatabaseSession::class;
	}

	/**
	 * This will get the session adapter.
	 *
	 * @return string
	 */
	protected static function setType(): string
	{
		return (self::$type ?? (self::$type = self::getConfigType()));
	}

	/**
	 * This will get the session type.
	 *
	 * @return string
	 */
	protected static function getType(): string
	{
		return self::setType();
	}

	/**
	 * This will get the session instance.
	 *
	 * @return SessionInterface
	 */
	public static function getInstance(): SessionInterface
	{
		/**
		 * @var object $type
		 */
		$type = self::getType();
		return self::$instance ?? (self::$instance = $type::getInstance());
	}

	/**
	 * This will start the session and close it to stop session
	 * locking.
	 *
	 * @return object
	 */
	public static function init(): object
	{
		/**
		 * @var object $type
		 */
		$type = self::getType();
		return $type::init();
	}

	/**
	 * This will get the session id.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		$session = static::getInstance();
		return $session->getId();
	}

	/**
	 * This will get the value of a key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get(string $key): mixed
	{
		$session = static::getInstance();
		return $session->{$key} ?? null;
	}

	/**
	 * This will destroy the session.
	 *
	 * @return void
	 */
	public static function destroy(): void
	{
		$session = static::getInstance();
		$session->destroy();
	}
}
