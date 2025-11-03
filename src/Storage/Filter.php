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
	 * Retrieves the value for a filter entry.
	 *
	 * @param mixed $value
	 * @param array $params
	 * @param bool $isSnakeCase
	 * @return mixed
	 */
	protected static function getValue(mixed $value, array &$params, bool $isSnakeCase): mixed
	{
		/**
		 * This is a raw sql.
		 */
		if (!is_array($value))
		{
			return $value;
		}

		/**
		 * If a first item is an array, this is not to be modified, just merge the params
		 * and return the raw SQL.
		 */
		$firstItem = $value[1] ?? null;
		if (is_array($firstItem))
		{
			$params = array_merge($params, $firstItem);
			return $value[0];
		}

		/**
		 * This will handle the array [key, value] or [key, operator, value] and replace
		 * the key with a prepared column name and the value with a placeholder.
		 */
		$value[0] = self::prepareColumn($value[0], $isSnakeCase);

		/**
		 * This will get the last element of the array and assign it to $param
		 * to replace it with a placeholder.
		 */
		$valueCount = count($value);
		$end = $valueCount - 1;
		$param = $value[$end];
		$value[$end] = '?';

		if ($valueCount === 3)
		{
			$value[1] = self::filterOperator((string)$value[1]);
		}
		else if ($valueCount > 3)
		{
			// Invalid format, reset value
			$value = [];
		}

		$params[] = $param;
		return [...$value];
	}

	/**
	 * Filters the operator to ensure it's allowed.
	 *
	 * @param string $operator
	 * @return string
	 */
	protected static function filterOperator(string $operator): string
	{
		return (in_array($operator, self::allowedOperators(), true))
			? $operator
			: '=';
	}

	/**
	 * Returns the list of allowed operators.
	 *
	 * @return array
	 */
	protected static function allowedOperators(): array
	{
		return [
			'=',
			'!=',
			'<',
			'>',
			'<=',
			'>=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'IS NULL',
			'IS NOT NULL',
			'BETWEEN',
			'NOT BETWEEN'
		];
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
			foreach ($filter as $key => $item)
			{
				$key = self::prepareColumn($key, $isSnakeCase);

				$value = (is_array($item)) ? [$key, ...$item] : [$key, $item];
				$value = self::getValue($value, $params, $isSnakeCase);

				$filters[] = $value;
			}
		}
		else
		{
			foreach ($filter as $item)
			{
				$value = self::getValue($item, $params, $isSnakeCase);
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
