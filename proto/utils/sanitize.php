<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * Sanitize Utility Class
 *
 * Provides methods for sanitizing and filtering input data.
 *
 * @package Proto\Utils
 */
class Sanitize
{
	/**
	 * Removes script tags from an HTML string.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function cleanHtml(string $str): string
	{
		return trim(self::removeScripts($str));
	}

	/**
	 * Removes potentially harmful script tags from an HTML string.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function removeScripts(string $str): string
	{
		$patterns = [
			'/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', // Remove <script>...</script> tags
			'/<\?.*?\?>/s', // Remove PHP tags
		];
		return preg_replace($patterns, '', $str) ?? $str;
	}

	/**
	 * Removes non-alphanumeric characters except underscores and dots from a column name.
	 *
	 * @param string $col
	 * @return string
	 */
	public static function cleanColumn(string $col): string
	{
		return preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
	}

	/**
	 * Recursively sanitizes an array or object.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function clean(mixed $data): mixed
	{
		if (is_null($data) || is_int($data) || is_bool($data))
		{
			return $data;
		}

		if (is_array($data))
		{
			foreach ($data as &$value)
			{
				$value = self::clean($value);
			}
		}
		elseif (is_object($data))
		{
			foreach ($data as $key => $value)
			{
				$data->$key = self::clean($value);
			}
		}
		else
		{
			$data = self::sanitizeString((string) $data);
		}

		return $data;
	}

	/**
	 * Removes HTML tags and normalizes slashes from a string.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function sanitizeString(string $str): string
	{
		$str = strip_tags($str);
		$str = str_replace(['\\\\', '\\\'', '\\"'], ['\\', '\'', ''], $str);
		return trim($str);
	}

	/**
	 * Recursively sanitizes data for safe HTML rendering.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function cleanHtmlEntities(mixed $data): mixed
	{
		if (is_null($data) || is_int($data) || is_bool($data))
		{
			return $data;
		}

		if (is_array($data))
		{
			foreach ($data as &$value)
			{
				$value = self::cleanHtmlEntities($value);
			}
		}
		elseif (is_object($data))
		{
			foreach ($data as $key => $value)
			{
				$data->$key = self::cleanHtmlEntities($value);
			}
		}
		else
		{
			$data = self::htmlEntities((string) $data);
		}

		return $data;
	}

	/**
	 * Encodes a string for safe HTML output.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function htmlEntities(string $str): string
	{
		return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}