<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Http\Router\Request;
use Modules\Developer\Storage\TableStorage;

/**
 * TableController
 *
 * Handles table storage operations.
 *
 * @package Modules\Developer\Controllers
 */
class TableController extends Controller
{
	/**
	 * Gets the table storage instance.
	 *
	 * @param string $connection Database connection name.
	 * @param string $tableName Name of the table.
	 * @return TableStorage
	 */
	protected function getStorage(string $connection, string $tableName): TableStorage
	{
		return new TableStorage($connection, $tableName);
	}

	/**
	 * Retrieves the table columns.
	 *
	 * @param Request $req The request object.
	 * @return array List of columns in the table.
	 */
	public function getColumns(Request $req): array
	{
		$connection = $req->input('connection');
		$tableName = $req->input('tableName');
		if (!$connection || !$tableName)
		{
			return [];
		}

		$storage = $this->getStorage($connection, $tableName);
		return $storage->getColumns();
	}
}