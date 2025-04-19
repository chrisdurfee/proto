<?php declare(strict_types=1);
namespace Modules\User\Storage\Multifactor;

use Proto\Storage\Storage;
use Proto\Utils\Sanitize;

/**
 * UserAuthedLocationStorage
 *
 * This will handle the storage for the user authenticated locations.
 *
 * @package Modules\User\Storage\Multifactor
 */
class UserAuthedLocationStorage extends Storage
{
	/**
	 * This will verify if the location exists for the user.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists($data): bool
	{
		$rows = $this->select('id')
            ->where("{$this->alias}.region_code = ?", "{$this->alias}.country_code = ?", "{$this->alias}.postal = ?")
			->limit(1)
			->fetch([$data->region_code, $data->country_code, $data->postal]);

		return $this->checkExistCount($rows);
	}

    /**
     * This will get the insert parameters for the location.
     *
     * @param object $data
     * @return bool
     */
	protected function getParams(object $data): object
	{
		$cols = [];
		$params = [];
		$placeholders = [];

		foreach ($data as $key => $val)
		{
			if ($key === 'position')
			{
				$parts = explode(' ', $val);

				// this will add two params for the lat and long
				$params = array_merge($params, $parts);
				array_push($placeholders, 'POINT(?, ?)');
			}
			else
			{
				array_push($params, $val);
				array_push($placeholders, '?');
			}

			$key = Sanitize::cleanColumn($key);
			$key = "`{$key}`";
			array_push($cols, $key);
		}

		return (object)[
			'cols' => $cols,
			'params' => $params,
			'placeholders' => $placeholders
		];
	}

    /**
     * This will insert the location into the database.
     *
     * @param object $data
     * @return bool
     */
    public function insert(object $data): bool
	{
		$insertData = $this->getParams($data);
		$result = $this->table()
			->insert()
			->fields($insertData->cols)
			->values($insertData->placeholders)
            ->execute($insertData->params);

		if (!isset($data->id))
		{
			$this->setModelId($result);
		}
		return $result;
	}

    /**
     * This will get the update parameters for the location.
     *
     * @param object $data
     * @return object
     */
    protected function getUpdateParams(object $data): object
	{
		$cols = [];
		$params = [];

		foreach ($data as $key => $val)
		{
			$key = Sanitize::cleanColumn($key);
			if ($key === 'id')
			{
				continue;
			}

			if ($key === 'position')
			{
				$parts = explode(' ', $val);
				$params = array_merge($params, $parts);
				$key = "`{$key}` = POINT(?, ?)";
			}
			else
			{
				array_push($params, $val);
				$key = "`{$key}` = ?";
			}

			array_push($cols, $key);
		}

		array_push($params, $data->id);

		return (object)[
			'cols' => $cols,
			'params' => $params
		];
	}

    /**
     * This will update the location in the database.
     *
     * @return bool
     */
    public function update(): bool
	{
		$data = $this->getUpdateData();
		$updateData = $this->getUpdateParams($data);

		return $this->table()
			->update(...$updateData->cols)
			->where("id = ?")
            ->execute($updateData->params);
	}
}