<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

/**
 * UniqueToken
 *
 * Generates a cryptographically secure unique token.
 *
 * @package Proto\Utils\Encryption
 */
class UniqueToken
{
	/**
	 * Generates a unique token using cryptographically secure random bytes.
	 *
	 * @param int $length The desired token length in bytes (defaults to 128).
	 * @return string The hexadecimal token.
	 */
	public static function generate(int $length = 128): string
	{
		return bin2hex(random_bytes($length));
	}
}