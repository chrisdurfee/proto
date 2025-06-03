<?php declare(strict_types=1);
namespace Modules\Auth\Storage\Multifactor;

use Proto\Storage\Storage;
use Proto\Utils\Sanitize;

/**
 * UserAuthedLocationStorage
 *
 * Handles persistence for authenticated user locations.
 *
 * @package Modules\Auth\Storage\Multifactor
 */
class UserAuthedLocationStorage extends Storage
{
	/**
	 * Verifies that the location already exists for the user.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function exists(object $data): bool
	{
		$rows = $this->select('id')
			->where(
				"{$this->alias}.region_code = ?",
				"{$this->alias}.country_code = ?",
				"{$this->alias}.postal = ?"
			)
			->limit(1)
			->fetch([$data->region_code, $data->country_code, $data->postal]);

		return $this->checkExistCount($rows);
	}

	/**
	 * Inserts a new location row.
	 *
	 * @param object $data
	 * @return bool
	 */
	public function insert(object $data): bool
	{
		$params = $this->buildParams($data);
		$result = $this->table()
			->insert()
			->fields($params->cols)
			->values($params->placeholders)
			->execute($params->params);

		if (!isset($data->id))
		{
			$this->setModelId($result);
		}

		return $result;
	}

	/**
	 * Updates an existing location row.
	 *
	 * @return bool
	 */
	public function update(): bool
	{
		$data = $this->getUpdateData();
		$params = $this->buildParams($data, true);

		return $this->table()
			->update(...$params->cols)
			->where('id = ?')
			->execute($params->params);
	}

	/**
	 * Builds column names, placeholders, and params for
	 * both insert and update operations.
	 *
	 * @param object $data
	 * @param bool $forUpdate
	 * @return object
	 */
	private function buildParams(object $data, bool $forUpdate = false): object
	{
		$cols = [];
		$params = [];
		$placeholders = [];

		foreach ($data as $key => $val)
		{
			if ($forUpdate && $key === 'id')
			{
				continue;
			}

			$cleanKey = '`' . Sanitize::cleanColumn($key) . '`';

			// Special handling for POINT(column)
			if ($key === 'position')
			{
				$parts = explode(' ', $val); // [lat, lon]
				$params = array_merge($params, $parts);

				if ($forUpdate)
				{
					$cols[] = "{$cleanKey} = POINT(?, ?)";
				}
				else
				{
					$cols[] = $cleanKey;
					$placeholders[] = 'POINT(?, ?)';
				}
			}
			// Standard scalar column
			else
			{
				$params[] = $val;

				if ($forUpdate)
				{
					$cols[] = "{$cleanKey} = ?";
				}
				else
				{
					$cols[] = $cleanKey;
					$placeholders[] = '?';
				}
			}
		}

		// Bind ID at the end for update statements
		if ($forUpdate)
		{
			$params[] = $data->id;
			return (object)[
				'cols' => $cols,
				'params' => $params
			];
		}

		return (object)[
			'cols' => $cols,
			'params' => $params,
			'placeholders' => $placeholders
		];
	}
}
