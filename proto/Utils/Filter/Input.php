<?php declare(strict_types=1);
namespace Proto\Utils\Filter;

use Proto\Utils\Filter\Filter;

/**
 * Input
 *
 * Handles sanitization and filtering of user input.
 *
 * @package Proto\Utils\Filter
 */
class Input extends Filter
{
	/**
	 * Filters an input value.
	 *
	 * @param int $inputType The input type (e.g., INPUT_GET, INPUT_POST).
	 * @param string|null $key The input key.
	 * @return string Sanitized input value or an empty string if not found.
	 */
	protected static function filter(int $inputType, ?string $key): string
	{
		if ($key === null || trim($key) === '')
		{
			return '';
		}

		$value = filter_input($inputType, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		return $value ?? '';
	}

	/**
	 * Retrieves and sanitizes a GET input.
	 *
	 * @param string|null $key The GET key.
	 * @return string The sanitized input value.
	 */
	public static function get(?string $key): string
	{
		return self::filter(INPUT_GET, $key);
	}

	/**
	 * Retrieves and sanitizes a POST input.
	 *
	 * @param string|null $key The POST key.
	 * @return string The sanitized input value.
	 */
	public static function post(?string $key): string
	{
		return self::filter(INPUT_POST, $key);
	}

	/**
	 * Retrieves and sanitizes a COOKIE input.
	 *
	 * @param string|null $key The COOKIE key.
	 * @return string The sanitized input value.
	 */
	public static function cookie(?string $key): string
	{
		return self::filter(INPUT_COOKIE, $key);
	}

	/**
	 * Retrieves and sanitizes a SERVER input.
	 *
	 * @param string|null $key The SERVER key.
	 * @return string The sanitized input value.
	 */
	public static function server(?string $key): string
	{
		return self::filter(INPUT_SERVER, $key);
	}
}