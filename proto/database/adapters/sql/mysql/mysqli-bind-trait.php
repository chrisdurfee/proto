<?php declare(strict_types=1);
namespace Proto\Database\Adapters\SQL\Mysql;

use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

/**
 * MysqliBindTrait
 *
 * This will create the bind parameters for the mysqli adapter.
 *
 * @package Proto\Database\Adapters\SQL\Mysql
 */
trait MysqliBindTrait
{
	/**
	 * Set up the parameters array.
	 *
	 * @param array|object $params
	 * @return array
	 */
	protected static function setupParams(array|object $params = []): array
	{
		if (Arrays::isAssoc($params) || is_object($params))
		{
			$result = [];
			foreach ($params as $param)
			{
				$result[] = $param;
			}
			$params = $result;
		}
		return $params;
	}

	/**
	 * Create query parameters from the provided data.
	 *
	 * @param array|object $data
	 * @param string $id
	 * @param bool $guard
	 * @return object
	 */
	protected function createParamsFromData(
		array|object $data = [],
		string $id = 'id',
		bool $guard = false
	): object
	{
		$returnId = null;
		$values = [];
		$cols = [];

		if ($data)
		{
			foreach ($data as $key => $val)
			{
				if ($key === $id)
				{
					$returnId = $val;
				}

				$key = Sanitize::cleanColumn($key);
				$key = ($guard === true) ? "`{$key}`" : $key;
				array_push($cols, $key);
				array_push($values, $val);
			}
		}

		return (object)[
			'cols' => $cols,
			'values' => $values,
			'id' => $returnId
		];
	}

	/**
	 * Set up query placeholders.
	 *
	 * @param array|object $data
	 * @return string
	 */
	protected function setupPlaceholders(array|object $data): string
	{
		$placeholders = array_fill(0, count($data), '?');
		return implode(',', $placeholders);
	}

	/**
	 * Prepare column names for update or select statements.
	 *
	 * @param array|object $cols
	 * @return string
	 */
	protected function getPrepareColNames(array|object $cols): string
	{
		$set = [];
		foreach ($cols as $col)
		{
			array_push($set, "{$col}=?");
		}
		return implode(',', $set);
	}

	/**
	 * Set up update pairs for the query.
	 *
	 * @param object $params
	 * @return string
	 */
	protected function setUpdatePairs(object $params): string
	{
		$cols = $params->cols;

		if (is_null($params->id) || sizeof($cols) < 1)
		{
			return '';
		}

		return $this->getPrepareColNames($cols);
	}
}