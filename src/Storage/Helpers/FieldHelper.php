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
	 * @param string|null $alias The alias for the fields.
	 *
	 * @return array|null
	 */
	public static function formatFields(?array $fields, bool $isSnakeCase = false, ?string $alias = null): ?array
	{
		if (!$fields || count($fields) < 1)
		{
			return $fields;
		}

		$cols = [];
		foreach ($fields as $field)
		{
			// If field is an array, it might be [fieldName, alias] or [[raw_sql], alias]
			// Pass the table alias to formatField so it can handle prepending correctly
			if (is_array($field))
			{
				$cols[] = self::formatField($field, $isSnakeCase, $alias);
			}
			else
			{
				// Simple string field - prepend alias if provided
				if ($alias)
				{
					$field = "{$alias}.{$field}";
				}
				$cols[] = self::formatField($field, $isSnakeCase);
			}
		}

		return $cols;
	}

	/**
	 * Formats a single field.
	 *
	 * @param mixed $field The field definition.
	 * @param bool $isSnakeCase Indicates whether to convert to snake_case.
	 * @param string|null $tableAlias Optional table alias to prepend to field names.
	 *
	 * @return mixed
	 */
	public static function formatField(mixed $field, bool $isSnakeCase = false, ?string $tableAlias = null): mixed
	{
		if (!is_array($field))
		{
			return self::prepareFieldName($field, $isSnakeCase);
		}

		// raw sql
		if (count($field) < 2)
		{
			return $field;
		}

		// sql with alias
		if (!is_array($field[0]))
		{
			// This is [fieldName, alias] format
			// Prepend table alias to field name if provided
			$fieldName = $field[0];
			if ($tableAlias && strpos($fieldName, '.') === false)
			{
				$fieldName = "{$tableAlias}.{$fieldName}";
			}

			return [
				self::prepareFieldName($fieldName, $isSnakeCase),
				self::prepareFieldName($field[1], $isSnakeCase)
			];
		}

		// raw sql with alias
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
