<?php declare(strict_types=1);
namespace Proto\Utils;

use Proto\Base;

/**
 * Arrays
 *
 * This will handle the arrays.
 *
 * @package Proto\Utils
 */
class Arrays extends Base
{
	/**
	 * This will check if the array is associative.
	 *
	 * @param array $array
	 * @return bool
	 */
    public static function isAssoc(array $array): bool
	{
		$keys = static::keys($array);
		return (static::keys($keys) !== $keys);
	}

	/**
	 * This will get the difference between two arrays.
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
	 * This will get the values of the array.
	 *
	 * @param array $items
	 * @return array
	 */
    public static function values(array $items): array
    {
        return array_values($items);
    }

	/**
	 * This will get the keys of the array.
	 *
	 * @param array $items
	 * @return array
	 */
    public static function keys(array $items): array
    {
        return array_keys($items);
    }
}
