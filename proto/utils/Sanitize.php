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
	 * Cleans an HTML string by removing scripts and dangerous tags.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function cleanHtml(string $str): string
	{
		$str = self::removeScripts($str);
		$str = self::stripDangerousTags($str);
		$str = self::stripUnsafeAttributes($str);
		return trim($str);
	}

	/**
	 * Removes <script> tags, PHP tags, inline event handlers, and javascript: URLs.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function removeScripts(string $str): string
	{
		$patterns = [
			'/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', // Remove <script>...</script> tags
			'/<\?.*?\?>/s', // Remove PHP tags
			'/javascript\s*:\s*/i', // Remove javascript: URLs
			'/<link\b[^<]*(?:(?!<\/link>)<[^<]*)*<\/link>/i', // Remove <link> tags that may load scripts
		];

		return preg_replace($patterns, '', $str) ?? $str;
	}

	/**
	 * Strips dangerous tags like <iframe>, <object>, and <embed> from an HTML string.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function stripDangerousTags(string $str): string
	{
		return preg_replace('/<(iframe|object|embed|form|meta|base|applet|svg)[^>]*>.*?<\/\1>/is', '', $str);
	}

	/**
	 * Removes unsafe attributes like inline event handlers (on*) and styles.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function stripUnsafeAttributes(string $str): string
	{
		return preg_replace('/\s*(on\w+|style|href)\s*=\s*(["\']?).*?\2/si', '', $str);
	}

	/**
	 * Removes non-alphanumeric characters except underscores and dots from a column name.
	 *
	 * @param string $col
	 * @return string
	 */
	public static function cleanColumn(string $col): string
	{
		return (string) preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
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
	 * Encodes a string for safe HTML output, removing dangerous elements.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function htmlEntities(string $str): string
	{
		$str = self::cleanHtml($str);
		return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}