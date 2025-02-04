<?php declare(strict_types=1);
namespace Proto\Auth;

use Proto\Http\Session;

/**
 * Gate
 *
 * This will be the base class for all auth gates.
 *
 * @package Proto\Auth
 * @abstract
 */
abstract class Gate
{
    /**
	 * This will setup the session.
	 *
	 * @return void
	 */
	public function __construct()
	{
		static::getSession();
	}

    /**
	 * @var object $session
	 */
	protected static object $session;

	/**
	 * This will get a value from the session.
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected static function get(string $key): mixed
	{
		return self::$session->{$key} ?? null;
	}

	/**
	 * This will set a value to the session.
	 *
	 * @param string $key
	 * @return void
	 */
	protected static function set(string $key, $value): void
	{
		self::$session->{$key} = $value;
	}

	/**
	 * This will get the session.
	 *
	 * @return object
	 */
	protected static function getSession(): object
	{
		return self::$session ?? (self::$session = Session::init());
	}
}
