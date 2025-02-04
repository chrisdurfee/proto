<?php declare(strict_types=1);
namespace Proto\Http\Session;

/**
 * SessionInterface
 *
 * This will define the session interface.
 *
 * @package Proto\Http\Session
 */
interface SessionInterface
{
    /**
	 * This setup the session adapter.
	 *
	 * @return SessionInterface
	 */
	public static function init(): SessionInterface;

	/**
	 * This will get the session id.
	 *
	 * @return string
	 */
	public static function getId(): string;

	/**
	 * This will set a session value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void;

	/**
	 * This will get a session value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed;

	/**
	 * This will unset a session value.
	 *
	 * @param string $key
	 */
	public function __unset(string $key): void;

	/**
	 * This will destroy the session.
	 *
	 * @return bool
	 */
	public function destroy(): bool;
}
