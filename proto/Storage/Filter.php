<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Utils\Strings;
use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

/**
 * Class Filter
 *
 * Handles filtering operations.
 *
 * @package Proto\Storage
 */
class Filter
{
	/**
	 * Decamelizes a string.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function decamelize(string $str): string
	{
		return Strings::snakeCase($str);
	}

	/**
	 * Retrieves the filter array.
	 *
	 * @param array|object|null $filter
	 * @return array
	 */
	public static function get(array|object|null $filter): array
	{
		if (!$filter)
		{
			return [];
		}

		if (is_object($filter))
		{
			$filter = (array)$filter;
		}

		return (count($filter) > 0) ? $filter : [];
	}

	/**
	 * Sets up the filter.
	 *
	 * @param mixed $filter
	 * @param array $params
	 * @param bool $isSnakeCase
	 * @return array
	 */
	public static function setup(
		mixed $filter = null,
		array &$params = [],
		bool $isSnakeCase = true
	): array
	{
		$filter = self::get($filter);
		if (count($filter) < 1)
		{
			return [];
		}

		$filters = [];
		if (Arrays::isAssoc($filter))
		{
			foreach ($filter as $key => $val)
			{
				$key = self::prepareColumn($key, $isSnakeCase);
				$filters[] = [$key, '?'];
				$params[] = $val;
			}
		}
		else
		{
			foreach ($filter as $item)
			{
				if (!is_array($item))
				{
					$filters[] = $item;
					continue;
				}

				$value = null;
				$firstItem = $item[1] ?? null;
				if (is_array($firstItem))
				{
					$params = array_merge($params, $firstItem);
					$value = $item[0];
				}
				else
				{
					$item[0] = self::prepareColumn($item[0], $isSnakeCase);
					$end = count($item) - 1;
					$param = $item[$end];
					$item[$end] = '?';
					$value = [...$item];
					$params[] = $param;
				}

				$filters[] = $value;
			}
		}

		return $filters;
	}

	/**
	 * Prepares the column name.
	 *
	 * @param string $field
	 * @param bool $isSnakeCase
	 * @return string
	 */
	protected static function prepareColumn(string $field, bool $isSnakeCase = true): string
	{
		$columnName = ($isSnakeCase) ? self::decamelize($field) : $field;
		return Sanitize::cleanColumn($columnName);
	}
}
