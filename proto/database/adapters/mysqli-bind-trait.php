<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

use Proto\Utils\Arrays;
use Proto\Utils\Sanitize;

trait MysqliBindTrait
{
	/**
	 * This will setup the params.
	 *
	 * @param array|object $params
	 * @return array
	 */
	protected static function setupParams($params = []): array
	{
		if(Arrays::isAssoc($params) || is_object($params))
		{
			$result = [];
			foreach($params as $param)
			{
				$result[] = $param;
			}
			$params = $result;
		}
		return $params;
	}

	/**
	 * This will get the query params from an object or array
	 * and allow the column to be sanitized
	 *
	 * @param array|object $data
	 * @param string $id
	 * @param bool $guard
	 * @return object
	 */
	protected function createParamsFromData($data = [], string $id = 'id', bool $guard = false): object
	{
		$returnId = null;
		$values = [];
		$cols = [];

		if($data)
		{
			foreach($data as $key => $val)
			{
				if($key === $id)
				{
					$returnId = $val;
				}

				$key = Sanitize::cleanColumn($key);
				$key = ($guard === true)? "`{$key}`" : $key;
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
	 * This will setup the query placeholders.
	 *
	 * @param array|object $data
	 * @return string
	 */
	protected function setupPlaceholders($data): string
	{
		$placeholders = array_fill(0, count($data), '?');
		return implode(',', $placeholders);
	}

	/**
	 * This will prepare the column names used in an
	 * update or select statement.
	 *
	 * @param array|object $cols
	 * @return string
	 */
	protected function getPrepareColNames($cols): string
	{
		/* this will convert the cols to a set string
		to prepare the query */
		$set = [];
		foreach($cols as $col)
		{
			array_push($set, "{$col}=?");
		}
		return implode(',', $set);
	}

    /**
     * This will setup the update pairs
     *
     * @param object $params
     * @return string
     */
    protected function setUpdatePairs(object $params): string
    {
		$cols = $params->cols;

		/* this will check to stop any query that doesn't
		have an id or set columns to update. */
		if(is_null($params->id) || sizeof($cols) < 1)
		{
			return '';
		}

		return $this->getPrepareColNames($cols);
    }
}