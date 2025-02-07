<?php declare(strict_types=1);
namespace Proto\Auth\Gates;

use Proto\Http\Session;

/**
 * Class Gate
 *
 * Base class for authentication gates.
 *
 * @package Proto\Auth
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
}