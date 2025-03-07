<?php declare(strict_types=1);
namespace Proto\Http;

/**
 * Token
 *
 * Handles the secure generation, storage, retrieval, and management of tokens.
 * Tokens are stored in cookies and used for authentication or other purposes.
 *
 * @package Proto\Http
 */
class Token
{
	/**
	 * Maximum token size in bytes.
	 */
	protected const MAX_SIZE = 512;

	/**
	 * Default token duration in days.
	 */
	protected const DEFAULT_DURATION = 120;

	/**
	 * Generates a secure token and stores it in a cookie.
	 *
	 * @param int $length Length of the token in bytes (bin2hex will double this size).
	 * @param int $expires Expiration timestamp (defaults to `DEFAULT_DURATION` days from now).
	 * @param string $name Name of the cookie.
	 * @return string Generated token.
	 */
	public static function create(int $length = 256, int $expires = -1, string $name = 'token'): string
	{
		return self::setCookie($length, $expires, $name);
	}

	/**
	 * Computes the expiration timestamp.
	 *
	 * @param int $expires Expiration timestamp or `-1` to use the default.
	 * @return int Computed expiration timestamp.
	 */
	protected static function computeExpiration(int $expires): int
	{
		return ($expires === -1) ? strtotime('+' . self::DEFAULT_DURATION . ' days') : $expires;
	}

	/**
	 * Ensures the token length does not exceed the allowed size.
	 *
	 * @param int $length Requested token length.
	 * @return int Adjusted token length.
	 */
	protected static function normalizeLength(int $length): int
	{
		return min($length, self::MAX_SIZE) / 2; // Bin2Hex doubles the size
	}

	/**
	 * Generates a secure random token.
	 *
	 * @param int $length Token length.
	 * @return string Securely generated token.
	 */
	protected static function generateToken(int $length): string
	{
		return bin2hex(random_bytes($length));
	}

	/**
	 * Sets a secure cookie with a generated token.
	 *
	 * @param int $length Token length.
	 * @param int $expires Expiration timestamp.
	 * @param string $name Cookie name.
	 * @return string Generated token.
	 */
	public static function setCookie(int $length, int $expires, string $name): string
	{
		$expires = self::computeExpiration($expires);
		$length = self::normalizeLength($length);
		$token = self::generateToken($length);

		(new Cookie($name, $token, $expires))->set();

		return $token;
	}

	/**
	 * Retrieves a token from a cookie.
	 *
	 * @param string $name Cookie name.
	 * @return string|null Token if exists, otherwise `null`.
	 */
	public static function get(string $name = 'token'): ?string
	{
		$cookie = Cookie::get($name);
		return $cookie ? $cookie->getValue() : null;
	}

	/**
	 * Removes a stored token by deleting its cookie.
	 *
	 * @param string $name Cookie name.
	 * @return void
	 */
	public static function remove(string $name = 'token'): void
	{
		Cookie::remove($name);
	}
}