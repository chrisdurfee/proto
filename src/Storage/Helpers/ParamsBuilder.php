<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Models\Model;
use Proto\Storage\DataTypes\DataType;
use Proto\Utils\Sanitize;

/**
 * ParamsBuilder
 *
 * Builds column names, placeholders, and parameters for INSERT/UPDATE
 * operations, with support for custom data types.
 *
 * @package Proto\Storage\Helpers
 */
class ParamsBuilder
{
	/**
	 * Build parameters for an INSERT operation.
	 *
	 * @param object $data The data object to insert
	 * @param Model $model The model instance
	 * @return object {cols: array, placeholders: array, params: array}
	 */
	public static function forInsert(object $data, Model $model): object
	{
		$cols = [];
		$placeholders = [];
		$params = [];

		foreach ($data as $key => $value)
		{
			$cleanKey = '`' . Sanitize::cleanColumn($key) . '`';
			$dataType = $model->getDataType($key);

			if ($dataType && $dataType->shouldHandle($value))
			{
				// Use custom data type
				$cols[] = $cleanKey;
				$placeholders[] = $dataType->getPlaceholder();
				$params = array_merge($params, $dataType->toParams($value));
			}
			else
			{
				// Standard scalar value
				$cols[] = $cleanKey;
				$placeholders[] = '?';
				$params[] = $value;
			}
		}

		return (object)[
			'cols' => $cols,
			'placeholders' => $placeholders,
			'params' => $params
		];
	}

	/**
	 * Build parameters for an UPDATE operation.
	 *
	 * @param object $data The data object to update
	 * @param Model $model The model instance
	 * @param string $idKey The identifier key name (to exclude from SET clause)
	 * @return object {cols: array, params: array}
	 */
	public static function forUpdate(object $data, Model $model, string $idKey = 'id'): object
	{
		$cols = [];
		$params = [];

		foreach ($data as $key => $value)
		{
			// Skip the ID field
			if ($key === $idKey)
			{
				continue;
			}

			$cleanKey = '`' . Sanitize::cleanColumn($key) . '`';
			$dataType = $model->getDataType($key);

			if ($dataType && $dataType->shouldHandle($value))
			{
				// Use custom data type
				$cols[] = $dataType->getUpdateClause($key);
				$params = array_merge($params, $dataType->toParams($value));
			}
			else
			{
				// Standard scalar value
				$cols[] = "{$cleanKey} = ?";
				$params[] = $value;
			}
		}

		// Append the ID value at the end for the WHERE clause
		if (isset($data->{$idKey}))
		{
			$params[] = $data->{$idKey};
		}

		return (object)[
			'cols' => $cols,
			'params' => $params
		];
	}
}
