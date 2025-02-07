<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

/**
 * Trait MysqliBindTrait
 *
 * Provides helper methods for preparing SQL queries.
 */
trait MysqliBindTrait
{
	/**
	 * Converts an associative array or object to a sequential array for binding.
	 *
	 * @param array|object $params
	 * @return array
	 */
	protected static function setupParams(array|object $params = []): array
	{
		if (Arrays::isAssoc($params) || is_object($params))
		{
			return array_values((array) $params);
		}
		return $params;
	}

	/**
	 * Extracts column names and values from an associative array or object.
	 *
	 * @param array|object $data The data array or object.
	 * @param string $idColumn The column name used for ID.
	 * @param bool $guard Whether to enclose column names in backticks for safety.
	 * @return object Contains 'cols', 'values', and 'id'.
	 */
	protected function createParamsFromData(array|object $data, string $idColumn = 'id', bool $guard = false): object
	{
		$returnId = null;
		$values = [];
		$cols = [];

		foreach ($data as $key => $val)
		{
			if ($key === $idColumn)
			{
				$returnId = $val;
			}

			$key = Sanitize::cleanColumn($key);
			$cols[] = $guard ? "`{$key}`" : $key;
			$values[] = $val;
		}

		return (object) [
			'cols' => $cols,
			'values' => $values,
			'id' => $returnId
		];
	}

	/**
	 * Generates a string of placeholders for a prepared statement.
	 *
	 * @param array $data The data for which placeholders are needed.
	 * @return string A comma-separated string of placeholders.
	 */
	protected function setupPlaceholders(array $data): string
	{
		return implode(',', array_fill(0, count($data), '?'));
	}

	/**
	 * Prepares column names for an update or select statement.
	 *
	 * @param array $cols Column names.
	 * @return string A formatted string for SQL statements.
	 */
	protected function getPrepareColNames(array $cols): string
	{
		return implode(', ', array_map(fn($col) => "{$col}=?", $cols));
	}

	/**
	 * Generates column-value pairs for an SQL update statement.
	 *
	 * @param object $params Object containing column names and values.
	 * @return string The formatted update statement pairs.
	 */
	protected function setUpdatePairs(object $params): string
	{
		if (is_null($params->id) || empty($params->cols))
		{
			return '';
		}

		return $this->getPrepareColNames($params->cols);
	}
}