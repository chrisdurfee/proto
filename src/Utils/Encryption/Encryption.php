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

		$key = env('encryption')->key;
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
		$key = env('encryption')->key;
		return Cipher::decrypt($text, $key);
	}
}
