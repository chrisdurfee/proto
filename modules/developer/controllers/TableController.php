<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Developer\App\Storage\TableStorage;

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
	 * Table storage instance.
	 *
	 * @var TableStorage|null
	 */
	protected ?TableStorage $storage = null;

	/**
	 * Constructor.
	 *
	 * Initializes the TableController with the given connection and table name.
	 *
	 * @param string $connection Database connection name.
	 * @param string $tableName Name of the table.
	 * @return void
	 */
	public function __construct(string $connection, string $tableName)
	{
		parent::__construct();
		$this->setStorage($connection, $tableName);
	}

	/**
	 * Sets the table storage instance.
	 *
	 * @param string $connection Database connection name.
	 * @param string $tableName Name of the table.
	 * @return void
	 */
	protected function setStorage(string $connection, string $tableName): void
	{
		$this->storage = new TableStorage($connection, $tableName);
	}

	/**
	 * Retrieves the table columns.
	 *
	 * @return array List of columns in the table.
	 */
	public function getColumns(): array
	{
		return $this->storage->getColumns();
	}
}