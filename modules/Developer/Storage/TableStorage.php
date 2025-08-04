<?php declare(strict_types=1);
namespace Modules\Developer\Storage;

use Proto\Database\Database;
use Proto\Database\Adapters\Mysqli;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Utils\Strings;

/**
 * TableStorage
 *
 * This class will handle the table storage.
 *
 * @package Modules\Developer\Storage
 */
class TableStorage
{
    /**
     * @var Mysqli|null
     */
    protected ?Mysqli $db = null;

    /**
     * @var string|null
     */
    protected ?string $tableName = null;

    /**
     * Constructor.
     *
     * @param string $connection
     * @param string $tableName
     */
    public function __construct(string $connection, string $tableName)
    {
        $this->tableName = $tableName;
        $this->connect($connection);
    }

    /**
	 * This will get a connection to the database.
	 *
     * @param string $connection
	 * @return void
	 */
    public function connect(string $connection): void
    {
        $db = new Database();
        $this->db = $db->connect($connection);
    }

    /**
	 * This will setup a query builder.
	 *
	 * @param string|null $alias
	 * @return QueryHandler
	 */
	public function table(?string $alias = null): QueryHandler
	{
		return $this->db->table($this->tableName, $alias);
	}

    /**
     * This will get the table columns.
     *
     * @return array
     */
    public function getColumns(): array
    {
        $sql = "SHOW COLUMNS FROM {$this->tableName};";
        $rows = $this->db->fetch($sql);
        if (count($rows) < 1)
        {
            return [];
        }

        return $this->format($rows);
    }

    /**
     * This will format the table rows to an row
     * of camel case properties.
     *
     * @param array $rows
     * @return array
     */
    protected function format(array $rows): array
    {
        $row = [];
        foreach ($rows as $item)
        {
            array_push($row, Strings::camelCase($item->Field));
        }
        return $row;
    }
}