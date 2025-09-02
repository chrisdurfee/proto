<?php declare(strict_types=1);

namespace Proto\Utils\Filter;

use Proto\Http\UploadFile;

/**
 * Validate
 *
 * Provides validation methods for different data types.
 *
 * @package Proto\Utils\Filter
 */
class Validate extends Filter
{
	/**
	 * Validates an integer.
	 *
	 * @param int|string|null $int
	 * @return bool
	 */
	public static function int(int|string|null $int): bool
	{
		return self::filter($int, FILTER_VALIDATE_INT);
	}

	/**
	 * Validates a float.
	 *
	 * @param float|string|null $number
	 * @return bool
	 */
	public static function float(float|string|null $number): bool
	{
		return self::filter($number, FILTER_VALIDATE_FLOAT);
	}

	/**
	 * Validates an IP address.
	 *
	 * @param string|null $ip
	 * @return bool
	 */
	public static function ip(?string $ip): bool
	{
		return self::filter($ip, FILTER_VALIDATE_IP);
	}

	/**
	 * Validates a MAC address.
	 *
	 * @param string|null $mac
	 * @return bool
	 */
	public static function mac(?string $mac): bool
	{
		return self::filter($mac, FILTER_VALIDATE_MAC);
	}

	/**
	 * Validates an email address.
	 *
	 * @param string|null $email
	 * @return bool
	 */
	public static function email(?string $email): bool
	{
		return self::filter($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Validates a non-empty string.
	 *
	 * @param string|null $string
	 * @return bool
	 */
	public static function string(?string $string): bool
	{
		return !empty($string) && is_string($string);
	}

	/**
	 * Validates a phone number (must be exactly 10 digits).
	 *
	 * @param string|null $phone
	 * @return bool
	 */
	public static function phone(?string $phone): bool
	{
		if ($phone === null || trim($phone) === '')
		{
			return false;
		}

		$phone = preg_replace('/\D/', '', $phone);
		return strlen($phone) === 10;
	}

	/**
	 * Validates a URL.
	 *
	 * @param string|null $url
	 * @return bool
	 */
	public static function url(?string $url): bool
	{
		return self::filter($url, FILTER_VALIDATE_URL);
	}

	/**
	 * Validates a boolean value.
	 *
	 * @param mixed $bool
	 * @return bool
	 */
	public static function bool(mixed $bool): bool
	{
		return filter_var($bool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
	}

	/**
	 * Validates a domain name.
	 *
	 * @param string|null $domain
	 * @return bool
	 */
	public static function domain(?string $domain): bool
	{
		return self::filter($domain, FILTER_VALIDATE_DOMAIN);
	}

	/**
	 * Validates an image file.
	 *
	 * @param mixed $image
	 * @return bool
	 */
	public static function image(mixed $image): bool
	{
		// Basic check - if it's not an array or UploadFile, it's not valid
		if (!is_array($image) && !($image instanceof UploadFile))
		{
			return false;
		}

		// If it's an array, check if it has the required upload file structure
		if (is_array($image))
		{
			return isset($image['tmp_name']) &&
				   isset($image['name']) &&
				   isset($image['type']) &&
				   isset($image['size']) &&
				   is_uploaded_file($image['tmp_name']);
		}

		// If it's an UploadFile, it should be valid
		return true;
	}

	/**
	 * Validates an input value using a given filter flag.
	 *
	 * @param mixed $key
	 * @param int $flag
	 * @param mixed $options
	 * @return bool
	 */
	protected static function filter(mixed $key, int $flag, mixed $options = []): bool
	{
		return ($key !== null) && (filter_var($key, $flag, $options) !== false);
	}
}