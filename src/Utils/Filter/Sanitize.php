<?php declare(strict_types=1);

namespace Proto\Utils\Filter;

/**
 * Sanitize
 *
 * Handles input sanitization for various data types.
 *
 * @package Proto\Utils\Filter
 */
class Sanitize extends Filter
{
	/**
	 * Sanitizes an email address.
	 *
	 * @param string|null $email
	 * @return string|null
	 */
	public static function email(?string $email): ?string
	{
		return self::filter($email, FILTER_SANITIZE_EMAIL);
	}

	/**
	 * Sanitizes a phone number (removes non-numeric characters).
	 *
	 * @param string|null $phone
	 * @return string|null
	 */
	public static function phone(?string $phone): ?string
	{
		if ($phone === null || trim($phone) === '')
		{
			return null;
		}

		$phone = preg_replace('/\D/', '', $phone);
		return (strlen($phone) === 10) ? $phone : null;
	}

	/**
	 * Sanitizes an IP address.
	 *
	 * @param string|null $ip
	 * @return string|null
	 */
	public static function ip(?string $ip): ?string
	{
		return filter_var($ip, FILTER_VALIDATE_IP) ?: null;
	}

	/**
	 * Sanitizes a MAC address.
	 *
	 * @param string|null $mac
	 * @return string|null
	 */
	public static function mac(?string $mac): ?string
	{
		return filter_var($mac, FILTER_VALIDATE_MAC) ?: null;
	}

	/**
	 * Sanitizes a boolean value.
	 *
	 * @param mixed $bool
	 * @return bool|null
	 */
	public static function bool(mixed $bool): ?bool
	{
		return filter_var($bool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}

	/**
	 * Sanitizes an integer value.
	 *
	 * @param int|string|null $int
	 * @return int|null
	 */
	public static function int(int|string|null $int): ?int
	{
		$result = self::filter($int, FILTER_SANITIZE_NUMBER_INT);
		return ($result !== null) ? (int)$result : null;
	}

	/**
	 * Sanitizes a float value.
	 *
	 * @param float|string|null $number
	 * @return float|null
	 */
	public static function float(float|string|null $number): ?float
	{
		$result = filter_var($number, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		return ($result !== false) ? (float)$result : null;
	}

	/**
	 * Sanitizes a string.
	 *
	 * @param string|null $string
	 * @return string|null
	 */
	public static function string(?string $string): ?string
	{
		return self::filter($string, FILTER_SANITIZE_SPECIAL_CHARS);
	}

	/**
	 * Sanitizes a URL.
	 *
	 * @param string|null $url
	 * @return string|null
	 */
	public static function url(?string $url): ?string
	{
		return self::filter($url, FILTER_SANITIZE_URL);
	}

	/**
	 * Sanitizes a domain name (alias for `url()`).
	 *
	 * @param string|null $url
	 * @return string|null
	 */
	public static function domain(?string $url): ?string
	{
		return self::url($url);
	}

	/**
	 * Validates and sanitizes an input value using a given filter flag.
	 *
	 * @param mixed $key
	 * @param int $flag
	 * @return mixed
	 */
	protected static function filter(mixed $key, int $flag): mixed
	{
		return ($key !== null) ? filter_var($key, $flag, FILTER_NULL_ON_FAILURE) : null;
	}
}