<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Utils\Strings;

/**
 * Class FieldHelper
 *
 * Provides helper methods for formatting field names.
 *
 * @package Proto\Storage\Helpers
 */
class FieldHelper
{
	/**
	 * Formats an array of fields.
	 *
	 * @param array|null $fields The list of fields.
	 * @param bool $isSnakeCase Indicates whether to convert to snake_case.
	 *
	 * @return array|null
	 */
	public static function formatFields(?array $fields, bool $isSnakeCase = false): ?array
	{
		if (!$fields || count($fields) < 1)
		{
			return $fields;
		}

		$cols = [];
		foreach ($fields as $field)
		{
			$cols[] = self::formatField($field, $isSnakeCase);
		}

		return $cols;
	}

	/**
	 * Formats a single field.
	 *
	 * @param mixed $field The field definition.
	 * @param bool $isSnakeCase Indicates whether to convert to snake_case.
	 *
	 * @return mixed
	 */
	public static function formatField(mixed $field, bool $isSnakeCase = false): mixed
	{
		if (!is_array($field))
		{
			return self::prepareFieldName($field, $isSnakeCase);
		}

		if (count($field) < 2)
		{
			return $field;
		}

		if (!is_array($field[0]))
		{
			return [
				self::prepareFieldName($field[0], $isSnakeCase),
				self::prepareFieldName($field[1], $isSnakeCase)
			];
		}

		return [$field[0], self::prepareFieldName($field[1], $isSnakeCase)];
	}

	/**
	 * Prepares a field name.
	 *
	 * @param string $field The field name.
	 * @param bool $isSnakeCase Indicates whether to convert to snake_case.
	 *
	 * @return string
	 */
	public static function prepareFieldName(string $field, bool $isSnakeCase = false): string
	{
		return $isSnakeCase ? self::decamelize($field) : $field;
	}

	/**
	 * Converts a camelCase string to snake_case.
	 *
	 * @param string $str The camelCase string.
	 *
	 * @return string
	 */
	public static function decamelize(string $str): string
	{
		return Strings::snakeCase($str);
	}
}
