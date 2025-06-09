<?php declare(strict_types=1);
namespace Proto\Http\Session;

/**
 * SessionInterface
 *
 * Defines the contract for session management implementations.
 *
 * @package Proto\Http\Session
 */
interface SessionInterface
{
	/**
	 * Initializes the session adapter.
	 *
	 * @return static
	 */
	public static function init(): static;

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string;

	/**
	 * Refresh the session ID.
	 *
	 * @return string
	 */
	public function refreshId(): string;

	/**
	 * Sets a session value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void;

	/**
	 * Retrieves a session value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed;

	/**
	 * Checks if a session key exists.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool;

	/**
	 * Unsets a session value.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void;

	/**
	 * Destroys the session and clears session data.
	 *
	 * @return bool
	 */
	public function destroy(): bool;
}