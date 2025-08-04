<?php declare(strict_types=1);
namespace Proto\Database\Adapters\Sql\Mysql;

use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

/**
 * MysqliQueryHelper class
 *
 * Provides helper methods for building SQL queries and processing query parameters.
 *
 * @package Proto\Database\Adapters\Sql\Mysql
 */
class MysqliQueryHelper
{
	/**
	 * Converts associative arrays or objects into sequential arrays for binding.
	 *
	 * @param array|object $params The parameters to process.
	 * @return array The formatted parameters.
	 */
	public function setupParams(array|object $params = []): array
	{
		return (Arrays::isAssoc($params) || is_object($params))
			? array_values((array) $params)
			: $params;
	}

	/**
	 * Extracts query parameters, sanitizing column names.
	 *
	 * @param array|object $data The data array or object.
	 * @param string $idColumn The column name used for ID.
	 * @param bool $guard Whether to enclose column names in backticks.
	 * @return object Contains 'cols', 'values', and 'id'.
	 */
	public function createParamsFromData(array|object $data, string $idColumn = 'id', bool $guard = false): object
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

			$cleanKey = Sanitize::cleanColumn($key);
			$cols[] = $guard ? "`{$cleanKey}`" : $cleanKey;
			$values[] = $val;
		}

		return (object) [
			'cols' => $cols,
			'values' => $values,
			'id' => $returnId,
		];
	}

	/**
	 * Generates a string of placeholders for a prepared statement.
	 *
	 * @param array $data The data for which placeholders are needed.
	 * @return string A comma-separated string of placeholders.
	 */
	public function setupPlaceholders(array $data): string
	{
		return implode(',', array_fill(0, count($data), '?'));
	}

	/**
	 * Formats column names for use in SQL update or select statements.
	 *
	 * @param array $cols Column names.
	 * @return string A formatted string for SQL statements.
	 */
	public function getPrepareColNames(array $cols): string
	{
		return implode(', ', array_map(fn($col) => "{$col}=?", $cols));
	}

	/**
	 * Generates column-value pairs for an SQL update statement.
	 *
	 * @param object $params Object containing column names and values.
	 * @return string The formatted update statement pairs.
	 */
	public function setUpdatePairs(object $params): string
	{
		return (!is_null($params->id) && !empty($params->cols))
			? $this->getPrepareColNames($params->cols)
			: '';
	}

	/**
	 * Binds parameters to a prepared statement.
	 *
	 * @param \mysqli_stmt $stmt The prepared statement.
	 * @param array|object $params The parameters to bind.
	 * @return void
	 */
	public function bindParams(\mysqli_stmt $stmt, array|object $params = []): void
	{
		if (empty($params))
		{
			return;
		}

		$params = $this->setupParams($params);
		$types  = str_repeat('s', count($params));
		$stmt->bind_param($types, ...$params);
	}
}