<?php declare(strict_types=1);
namespace Proto\Auth\Gates;

use Proto\Http\Session;

/**
 * Class Gate
 *
 * Base class for authentication gates.
 *
 * @package Proto\Auth\Gates
 * @abstract
 */
abstract class Gate
{
	/**
	 * @var ?object The session instance.
	 */
	protected static ?object $session = null;

	/**
	 * Initializes the session.
	 */
	public function __construct()
	{
		static::getSession();
	}

	/**
	 * Retrieves a value from the session.
	 *
	 * @param string $key The session key.
	 * @return mixed The session value or null if not found.
	 */
	protected static function get(string $key): mixed
	{
		return static::$session->{$key} ?? null;
	}

	/**
	 * Stores a value in the session.
	 *
	 * @param string $key The session key.
	 * @param mixed $value The value to store.
	 */
	protected static function set(string $key, mixed $value): void
	{
		static::$session->{$key} = $value;
	}

	/**
	 * Initializes and retrieves the session instance.
	 *
	 * @return object The session instance.
	 */
	protected static function getSession(): object
	{
		return static::$session ??= Session::init();
	}

	/**
	 * Resets the cached session reference.
	 *
	 * `$session` is static and shared by every gate subclass, so once cached
	 * it outlives a single request in long-running processes (Swoole,
	 * RoadRunner, persistent PHP-FPM workers), causing later visitors to
	 * reuse an earlier visitor's session (and CSRF token). Call between
	 * requests in those environments, alongside `Session::reset()`.
	 *
	 * Named `resetSessionCache()` (not `reset()`) because subclasses such as
	 * `CrossSiteRequestForgeryGate` already declare an unrelated *instance*
	 * `reset()` (clears the stored CSRF token). PHP fatals at class-load
	 * time if a subclass overrides a static parent method with a
	 * non-static one — that collision previously made every CSRF-gated
	 * request fatal as soon as this method existed as `reset()` here.
	 *
	 * @return void
	 */
	public static function resetSessionCache(): void
	{
		static::$session = null;
	}
}