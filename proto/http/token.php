<?php declare(strict_types=1);
namespace Proto\Http;

/**
 * Token
 *
 * Token class is responsible for creating, retrieving and managing tokens
 * to be stored in cookies. It ensures that the tokens are generated securely
 * and with appropriate size and duration.
 *
 * @package Proto\Http
 */
class Token
{
	/**
	 * Maximum size of the token.
	 *
	 * @var int $maxSize
	 */
	protected static int $maxSize = 512;

	/**
	 * Default duration of the token in days.
	 *
	 * @var int $duration
	 */
	protected static int $duration = 120;

	/**
	 * Creates a token using random bytes and bin2hex.
	 *
	 * @param int $length Length of the token to be created.
	 * @param int $expires Expiration time of the token. If not provided, defaults to 30 days.
	 * @param string $name Name of the cookie to store the token.
	 * @return string Generated token.
	 */
	public static function create(int $length = 128, int $expires = -1, string $name = 'token'): string
	{
		return self::setCookie($length, $expires, $name);
	}

	/**
	 * Prepares the expiration time. Default is 30 days.
	 *
	 * @param int $expires Expiration time of the token.
	 * @return int Prepared expiration time.
	 */
	protected static function setExpires(int $expires): int
	{
		return ($expires === -1) ? strtotime('+ ' . self::$duration . ' days') : $expires;
	}

	/**
	 * Prepares the length to be compatible with the database.
	 *
	 * @param int $length Length of the token to be created.
	 * @return int Prepared length.
	 */
	protected static function getLength(int $length): int
	{
		if ($length > self::$maxSize)
		{
			$length = self::$maxSize;
		}

		// Bin2Hex doubles the size of the random bytes stream
		return $length / 2;
	}

	/**
	 * Generates a secure token.
	 *
	 * @param int $length Length of the token to be created.
	 * @return string Generated token.
	 */
	protected static function getToken(int $length): string
	{
		return bin2hex(random_bytes($length));
	}

	/**
	 * Sets the cookie with the token.
	 *
	 * @param int $length Length of the token to be created.
	 * @param int $expires Expiration time of the token.
	 * @param string $name Name of the cookie to store the token.
	 * @return string Generated token.
	 */
	public static function setCookie(int $length, int $expires, string $name): string
	{
		$expires = self::setExpires($expires);
		$length = self::getLength($length);
		$token = self::getToken($length);

		$cookie = new Cookie($name, $token, $expires);
		$cookie->set();

		return $token;
	}

	/**
	 * Gets a cookie by name.
	 *
	 * @param string $name Name of the cookie.
	 * @return mixed Value of the cookie if it exists, otherwise null.
	 */
	public static function get(string $name = 'token'): mixed
	{
		return Cookie::get($name);
	}

	/**
	 * Removes a cookie with the token.
	 *
	 * @param string $name Name of the cookie to remove.
	 * @return void
	 */
	public static function remove(string $name = 'token'): void
	{
		Cookie::remove($name);
	}
}