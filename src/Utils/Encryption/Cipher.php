<?php declare(strict_types=1);
namespace Proto\Utils\Encryption;

use Proto\Utils\Util;

/**
 * Cipher
 *
 * Handles encryption and decryption using AES-256-CTR with HMAC for integrity verification.
 *
 * @package Proto\Utils\Encryption
 */
class Cipher extends Util
{
	/**
	 * Cipher type used for encryption.
	 */
	private const CIPHER = 'aes-256-ctr';

	/**
	 * Length of the HMAC hash.
	 */
	private const HMAC_LENGTH = 32;

	/**
	 * Prefix of the current ciphertext format: separate derived keys for
	 * encryption and MAC, and a MAC over IV + ciphertext checked before
	 * decrypting. Values without it use the legacy format.
	 */
	private const VERSION_PREFIX = 'v2.';

	/**
	 * Derives a purpose-bound subkey so the cipher and the MAC never share
	 * key material.
	 *
	 * @param string $key Master key.
	 * @param string $purpose "enc" or "mac".
	 * @return string Raw 32-byte key.
	 */
	protected static function deriveKey(string $key, string $purpose): string
	{
		return \hash_hkdf('sha256', $key, 32, 'proto-cipher-' . $purpose);
	}

	/**
	 * Retrieves the length of the initialization vector.
	 *
	 * @return int
	 */
	protected static function getIvLength(): int
	{
		$length = \openssl_cipher_iv_length(self::CIPHER);
		return $length !== false ? $length : 16; // Default IV length
	}

	/**
	 * Generates a secure initialization vector (IV).
	 *
	 * @return string
	 */
	protected static function getIv(): string
	{
		return \random_bytes(self::getIvLength());
	}

	/**
	 * Safely encodes a string using Base64 with URL-safe characters.
	 *
	 * @param string $message
	 * @return string
	 */
	protected static function safeB64Encode(string $message): string
	{
		$data = \base64_encode($message);
		return str_replace(['+', '/', '='], ['-', '_', ''], $data);
	}

	/**
	 * Safely decodes a Base64-encoded string.
	 *
	 * @param string $message
	 * @return string
	 */
	protected static function safeB64Decode(string $message): string
	{
		$data = str_replace(['-', '_'], ['+', '/'], $message);
		$mod4 = \strlen($data) % 4;
		if ($mod4)
		{
			$data .= \substr('====', $mod4);
		}
		return \base64_decode($data);
	}

	/**
	 * Encrypts a string using AES-256-CTR with HMAC for integrity verification.
	 *
	 * @param string $plainText The plaintext to encrypt.
	 * @param string $key The encryption key.
	 * @return string The Base64-encoded encrypted string.
	 */
	public static function encrypt(string $plainText, string $key): string
	{
		if ($plainText === '')
		{
			return '';
		}

		$iv = self::getIv();
		$encrypted = \openssl_encrypt(
			$plainText,
			self::CIPHER,
			self::deriveKey($key, 'enc'),
			OPENSSL_RAW_DATA,
			$iv
		);
		$hash = self::hash($iv . $encrypted, self::deriveKey($key, 'mac'));

		return self::VERSION_PREFIX . self::safeB64Encode($iv . $hash . $encrypted);
	}

	/**
	 * Generates an HMAC hash for integrity verification.
	 *
	 * @param string $data The data to hash.
	 * @param string $key The encryption key.
	 * @return string The raw binary hash.
	 */
	protected static function hash(string $data, string $key): string
	{
		return \hash_hmac('sha256', $data, $key, true);
	}

	/**
	 * Decrypts an encrypted string using AES-256-CTR and verifies its integrity using HMAC.
	 *
	 * @param string $encodedText The Base64-encoded encrypted string.
	 * @param string $key The encryption key.
	 * @return string|null The decrypted plaintext if valid, or null if the integrity check fails.
	 */
	public static function decrypt(string $encodedText, string $key): ?string
	{
		if ($encodedText === '')
		{
			return null;
		}

		if (\str_starts_with($encodedText, self::VERSION_PREFIX))
		{
			return self::decryptVersioned(\substr($encodedText, \strlen(self::VERSION_PREFIX)), $key);
		}

		// Legacy format: one key for cipher and MAC, MAC over ciphertext only.
		$text = self::safeB64Decode($encodedText);
		$ivLength = self::getIvLength();
		if (\strlen($text) < ($ivLength + self::HMAC_LENGTH))
		{
			return null; // Invalid data format
		}

		$iv = \substr($text, 0, $ivLength);
		$hash = \substr($text, $ivLength, self::HMAC_LENGTH);
		$rawText = \substr($text, $ivLength + self::HMAC_LENGTH);

		$calculatedHash = self::hash($rawText, $key);
		if (!\hash_equals($hash, $calculatedHash))
		{
			return null;
		}

		$decrypted = \openssl_decrypt($rawText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
		return $decrypted === false ? null : $decrypted;
	}

	/**
	 * Decrypts the current format, verifying the MAC (over IV and
	 * ciphertext) before touching the cipher.
	 *
	 * @param string $encoded Base64 body after the version prefix.
	 * @param string $key Master key.
	 * @return string|null
	 */
	protected static function decryptVersioned(string $encoded, string $key): ?string
	{
		$text = self::safeB64Decode($encoded);
		$ivLength = self::getIvLength();
		if (\strlen($text) < ($ivLength + self::HMAC_LENGTH))
		{
			return null;
		}

		$iv = \substr($text, 0, $ivLength);
		$hash = \substr($text, $ivLength, self::HMAC_LENGTH);
		$rawText = \substr($text, $ivLength + self::HMAC_LENGTH);

		$calculatedHash = self::hash($iv . $rawText, self::deriveKey($key, 'mac'));
		if (!\hash_equals($hash, $calculatedHash))
		{
			return null;
		}

		$decrypted = \openssl_decrypt($rawText, self::CIPHER, self::deriveKey($key, 'enc'), OPENSSL_RAW_DATA, $iv);
		return $decrypted === false ? null : $decrypted;
	}
}