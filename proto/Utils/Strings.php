<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * Strings Class
 *
 * Provides utility functions for string manipulation.
 *
 * @package Proto\Utils
 */
class Strings
{
	/**
	 * Converts a string to snake_case.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function snakeCase(string $str): string
	{
		return strtolower(preg_replace('/([a-z])([A-Z0-9])/', '$1_$2', $str));
	}

	/**
	 * Converts a string to kebab-case.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function kebabCase(string $str): string
	{
		return strtolower(preg_replace('/([a-z])([A-Z0-9])/', '$1-$2', $str));
	}

	/**
	 * Converts a string to hyphen-case.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function hyphen(string $str): string
	{
		$str = str_replace(['_', ' '], '-', $str);
		return strtolower(preg_replace('/([a-z])([A-Z0-9])/', '$1-$2', $str));
	}

	/**
	 * Converts a string to camelCase.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function camelCase(string $str): string
	{
		return lcfirst(preg_replace_callback('/(_|-)([a-z0-9])/', fn($matches) => strtoupper($matches[2]), $str));
	}

	/**
	 * Converts a class name to a file name.
	 *
	 * @param string $className
	 * @return string
	 */
	public static function classToFileName(string $className): string
	{
		return self::hyphen(str_replace('\\', '/', $className));
	}

	/**
	 * This will lowercase first char.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function lowercaseFirstChar(string $str): string
	{
		return \lcfirst($str);
	}

	/**
	 * This will map a snake case object to camel case.
	 *
	 * @param object $data
	 * @return object
	 */
	public static function mapToCamelCase(object $data): object
	{
		$obj = (object)[];

		foreach ($data as $key => $val)
		{
			if (\is_null($val))
			{
				continue;
			}

			$keyCamelCase = self::camelCase($key);
			$obj->{$keyCamelCase} = $val;
		}
		return $obj;
	}

	/**
	 * Converts a string to PascalCase.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function pascalCase(string $str): string
	{
		return ucfirst(self::camelCase($str));
	}

	/**
	 * Strips new lines from a string.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function stripNewlines(string $str): string
	{
		return trim(preg_replace('/\s+/', ' ', $str));
	}

	/**
	 * Removes the dollar symbol from an amount string.
	 *
	 * @param string $amount
	 * @return string
	 */
	public static function removeDollar(string $amount): string
	{
		return str_replace('$', '', $amount);
	}

	/**
	 * Extracts the domain from a URL string.
	 *
	 * @param string $url
	 * @return string
	 */
	public static function filterUrl(string $url): string
	{
		if (!$url)
		{
			return '';
		}

		$url = preg_replace(['/(^\/|\/$)/', '/(^https?:\/\/)/', '/(^www\.)/'], '', $url);
		return explode("/", $url, 2)[0];
	}

	/**
	 * Retrieves the path from a URL string.
	 *
	 * @param string $url
	 * @return string
	 */
	public static function getUrlPath(string $url): string
	{
		return parse_url($url, PHP_URL_PATH) ?? '';
	}

	/**
	 * Cleans a phone number by removing non-numeric characters.
	 *
	 * @param string $number
	 * @return string
	 */
	public static function cleanPhone(string $number): string
	{
		$number = preg_replace('/\D/', '', $number);
		return ($number[0] === '1') ? substr($number, 1) : $number;
	}

	/**
	 * Cleans an E.164 formatted phone number.
	 *
	 * @param string $number
	 * @return string
	 */
	public static function cleanE164Phone(string $number): string
	{
		return preg_replace('/[^0-9+]/', '', $number);
	}

	/**
	 * Formats a phone number.
	 *
	 * @param string $number
	 * @param string $format
	 * @return string
	 */
	public static function formatPhone(string $number, string $format = 'E.164'): string
	{
		return match ($format)
		{
			'E.164' => self::formatE164Phone($number),
			'NANP'  => preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $number),
			default => $number
		};
	}

	/**
	 * Formats a phone number to E.164 format.
	 *
	 * @param string $number
	 * @return string
	 */
	public static function formatE164Phone(string $number): string
	{
		$number = self::cleanPhone($number);
		return (strlen($number) === 11) ? "+{$number}" : "+1{$number}";
	}

	/**
	 * Removes non-alphanumeric characters from a string.
	 *
	 * @param string $text
	 * @param string $replace
	 * @return string
	 */
	public static function replaceNonAlphaNumeric(string $text, string $replace = ''): string
	{
		return preg_replace('/[^a-zA-Z0-9]/', $replace, $text);
	}

	/**
	 * Concatenates two strings.
	 *
	 * @param string $str1
	 * @param string $str2
	 * @return string
	 */
	public static function concat(string $str1, string $str2): string
	{
		return $str1 . $str2;
	}

	/**
	 * Masks a string by replacing characters with a mask symbol.
	 *
	 * @param string $string
	 * @param int $length
	 * @param string $mask
	 * @return string
	 */
	public static function mask(string $string, int $length = 4, string $mask = '*'): string
	{
		$chop = max(0, strlen($string) - $length);
		return str_repeat($mask, $chop) . substr($string, -$length);
	}

	/**
	 * Encodes an object into a parameter string.
	 *
	 * @param object $params
	 * @return string
	 */
	public static function encodeParams(object $params): string
	{
		return http_build_query((array) $params);
	}
}
