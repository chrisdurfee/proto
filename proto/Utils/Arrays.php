<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * Arrays Utility Class
 *
 * Provides utility functions for array manipulation.
 *
 * @package Proto\Utils
 */
class Arrays
{
	/**
	 * Checks if an array is associative.
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function isAssoc(array $array): bool
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}

	/**
	 * Returns the difference between two arrays.
	 *
	 * @param array $needles
	 * @param array $haystack
	 * @return array
	 */
	public static function diff(array $needles, array $haystack): array
	{
		return array_diff($needles, $haystack);
	}

	/**
	 * Returns the values of an array.
	 *
	 * @param array $items
	 * @return array
	 */
	public static function values(array $items): array
	{
		return array_values($items);
	}

	/**
	 * Returns the keys of an array.
	 *
	 * @param array $items
	 * @return array
	 */
	public static function keys(array $items): array
	{
		return array_keys($items);
	}
}
