<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Utils\Strings;
use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

/**
 * Filter
 *
 * This will handle the filter.
 *
 * @package Proto\Storage
 */
class Filter
{
	/**
	 * This will decamelise a string.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function decamelize(string $str): string
    {
        return Strings::snakeCase($str);
    }

    /**
     * This will get the filter array.
     *
     * @param array|object|null $filter
     * @return array
     */
	public static function get($filter): array
	{
		if (!$filter)
		{
			return [];
		}

		if (\is_object($filter))
		{
			$filter = (array)$filter;
		}

		return (\count($filter) > 0)? $filter : [];
	}

	/**
	 * This will set up the filter.
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
		if (\count($filter) < 1)
		{
			return [];
		}

		/* this will convert the cols to a set string
		to prepare the query */
		$filters = [];
		if (Arrays::isAssoc($filter))
		{
			foreach ($filter as $key => $val)
			{
				// this will decamelize the key to allow it
				// to be used in the query
				$key = self::prepareColumn($key, $isSnakeCase);
				array_push($filters, [$key, '?']);
				array_push($params, $val);
			}
		}
		else
		{
			foreach ($filter as $item)
			{
				if (is_array($item) === false)
				{
					// raw sql
					array_push($filters, $item);
					continue;
				}

                $value = null;
				$firstItem = $item[1] ?? null;
				if (is_array($firstItem))
				{
					// this will setup prebuilt param string
					$params = array_merge($params, $firstItem);
					$value = $item[0];
				}
				else
				{
					// this will setup the column name
					$item[0] = self::prepareColumn($item[0], $isSnakeCase);

					// this will replace the valu with a placeholder
					$end = count($item) - 1;
					$param = $item[$end];
					$item[$end] = '?';

					$value = [...$item];
                    array_push($params, $param);
				}

				array_push($filters, $value);
			}
		}

		return $filters;
	}

	/**
	 * This will prepare the column.
	 *
	 * @param string $field
	 * @param bool $isSnakeCase
	 * @return string
	 */
	protected static function prepareColumn(string $field, bool $isSnakeCase = true): string
	{
		$columnName = ($isSnakeCase)? self::decamelize($field) : $field;
		return Sanitize::cleanColumn($columnName);
	}
}
