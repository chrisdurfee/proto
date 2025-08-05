<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

use Proto\Utils\Util;

/**
 * Encryption
 *
 * Handles secure encryption and decryption using AES-256-CTR.
 *
 * @package Proto\Utils\Encryption
 */
class Encryption extends Util
{
	/**
	 * Secure key generated using `random_bytes(64)` converted to hex.
	 *
	 * Example:
	 * ```php
	 * $bytes = random_bytes(64);
	 * $hex   = bin2hex($bytes);
	 * ```
	 *
	 * @var string
	 */
	private const KEY = 'ae92b7c1a5d982e36f46d8739dfef6f748f89c2de54b3caed9cf20a56bda27d637ec4b2145f987a6bc319048ab82c7f1dcf46dca7a24935b5ae1f34a6173d938';

	/**
	 * Encrypts the given data using AES-256-CTR.
	 *
	 * @param mixed $data The data to encrypt (automatically JSON-encoded if not a string).
	 * @param string|null $key Optional encryption key (defaults to class constant).
	 * @return string The encrypted string.
	 */
	public static function encrypt(mixed $data, ?string $key = null): string
	{
		if (!is_string($data))
		{
			$data = \json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$key = $key ?? self::KEY;
		return Cipher::encrypt($data, $key);
	}

	/**
	 * Decrypts the given encrypted string.
	 *
	 * @param string $text The encrypted text.
	 * @param string|null $key Optional decryption key (defaults to class constant).
	 * @return string|null The decrypted string or null if decryption fails.
	 */
	public static function decrypt(string $text, ?string $key = null): ?string
	{
		$key = $key ?? self::KEY;
		return Cipher::decrypt($text, $key);
	}
}
