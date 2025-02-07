<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

use Proto\Utils\Sanitize;
use Proto\Database\Adapters\SQL\Mysql\MysqliBindTrait;
use Proto\Database\Adapters\SQL\SQL;

/**
 * Initialize SQL functions in the global scope.
 */
SQL::init();

/**
 * Mysqli adapter class.
 *
 * Provides a MySQLi implementation of the database adapter.
 *
 * @package Proto\Database\Adapters
 */
class Mysqli extends Adapter
{
	use MysqliBindTrait;

	/**
	 * Starts the database connection.
	 *
	 * @return bool True if connection was successful, false otherwise.
	 */
	protected function startConnection() : bool
	{
		$settings = $this->settings;
		$connection = new \mysqli(
			'p:' . $settings->host,
			$settings->username,
			$settings->password,
			$settings->database,
			$settings->port
		);

		if ($connection->connect_error)
		{
			$this->setLastError(new \Exception($connection->connect_error));
			return false;
		}

		$this->setConnection($connection);
		$connection->set_charset('utf8mb4');

		return true;
	}

	/**
	 * Stops the database connection.
	 *
	 * @return void
	 */
	protected function stopConnection() : void
	{
		if ($this->connection instanceof \mysqli)
		{
			$this->connection->close();
		}
	}

	/**
	 * Binds parameters to a prepared statement.
	 *
	 * @param \mysqli_stmt $stmt The prepared statement.
	 * @param array|object $params The parameters to bind.
	 * @return void
	 */
	protected static function bindParams(\mysqli_stmt $stmt, array|object $params = []) : void
	{
		if (empty($params))
		{
			return;
		}

		$params = self::setupParams($params);
		$types = str_repeat('s', count($params));
		$stmt->bind_param($types, ...$params);
	}

	/**
	 * Prepares a SQL statement.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return \mysqli_stmt|bool The prepared statement or false on failure.
	 */
	protected function prepare(string $sql, array|object $params = []) : \mysqli_stmt|bool
	{
		if (!$this->connected)
		{
			return false;
		}

		$stmt = $this->connection->prepare($sql);
		if (!$stmt)
		{
			$this->error($sql, $this->connection->error);
			return false;
		}

		self::bindParams($stmt, $params);
		return $stmt;
	}

	/**
	 * Prepares and executes a SQL statement.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return \mysqli_stmt|bool The executed statement or false on failure.
	 */
	protected function prepareAndExecute(string $sql, array|object $params = []) : \mysqli_stmt|bool
	{
		$stmt = $this->prepare($sql, $params);
		if (!$stmt)
		{
			return false;
		}

		if (!$stmt->execute())
		{
			$this->error($sql, $this->connection->error);
			return false;
		}

		return $stmt;
	}

	/**
	 * Executes a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	public function execute(string $sql, array|object $params = []) : bool
	{
		$db = $this->connect();
		if (!$db)
		{
			return false;
		}

		$stmt = $this->prepareAndExecute($sql, $params);
		if (!$stmt)
		{
			$this->error($sql, $this->connection->error);
			return false;
		}

		$this->setLastId($db->insert_id);
		$stmt->close();
		$this->disconnect();

		return true;
	}

	/**
	 * Fetches the results of a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @param string $resultType The type of results: 'object' or 'array'.
	 * @return array|bool The fetched results as an array, or false on failure.
	 */
	public function fetch(string $sql, array|object $params = [], string $resultType = 'object') : array|bool
	{
		$db = $this->connect();
		if (!$db)
		{
			return false;
		}

		$stmt = $this->prepareAndExecute($sql, $params);
		$rows = [];
		if ($stmt)
		{
			$rows = $this->fetchStatementResults($stmt, $resultType);
			$stmt->close();
		}

		$this->disconnect();
		return $rows;
	}

	/**
	 * Executes a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	public function query(string $sql, array|object $params = []) : bool
	{
		return $this->execute($sql, $params);
	}

	/**
	 * Enables or disables autocommit mode.
	 *
	 * @param bool $enable True to enable, false to disable.
	 * @return void
	 */
	public function autoCommit(bool $enable) : void
	{
		if (!$this->connected)
		{
			return;
		}

		$this->connection->autocommit($enable);
	}

	/**
	 * Begins a database transaction.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function beginTransaction() : bool
	{
		if (!$this->connected)
		{
			return false;
		}

		$result = $this->connection->begin_transaction();
		return $this->checkResult($result);
	}

	/**
	 * Checks the result of a database operation.
	 *
	 * @param bool $result The result to check.
	 * @return bool The original result.
	 */
	protected function checkResult(bool $result) : bool
	{
		if (!$result)
		{
			$this->setLastError($this->connection->error);
		}
		return $result;
	}

	/**
	 * Executes a transaction with a single query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	public function transaction(string $sql, array|object $params = []) : bool
	{
		if (!$this->beginTransaction())
		{
			return false;
		}

		$result = $this->execute($sql, $params);
		if (!$result)
		{
			$this->rollback();
			return false;
		}

		return $this->commit();
	}

	/**
	 * Commits a database transaction.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function commit() : bool
	{
		if (!$this->connected)
		{
			return false;
		}

		$result = $this->connection->commit();
		return $this->checkResult($result);
	}

	/**
	 * Rolls back a database transaction.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function rollback() : bool
	{
		if (!$this->connected)
		{
			return false;
		}

		$result = $this->connection->rollback();
		return $this->checkResult($result);
	}

	/**
	 * Inserts data into a table.
	 *
	 * @param string $tableName The table name.
	 * @param array|object $data The data to insert.
	 * @return bool True on success, false on failure.
	 */
	public function insert(string $tableName, array|object $data) : bool
	{
		$params = $this->createParamsFromData($data, 'id', true);

		$columns = implode(', ', $params->cols);
		$placeholders = $this->setupPlaceholders($params->cols);

		$sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders});";

		return $this->execute($sql, $params->values);
	}

	/**
	 * Updates data in a table.
	 *
	 * @param string $tableName The table name.
	 * @param array|object $data The data to update.
	 * @param string $idColumn The column representing the primary key.
	 * @return bool True on success, false on failure.
	 */
	public function update(string $tableName, array|object $data, string $idColumn = 'id') : bool
	{
		$params = $this->createParamsFromData($data, $idColumn, true);
		$updatePairs = $this->setUpdatePairs($params);

		if (empty($updatePairs))
		{
			return false;
		}

		$idColumn = Sanitize::cleanColumn($idColumn);
		$sql = "UPDATE {$tableName} SET {$updatePairs} WHERE {$idColumn} = ?;";

		$params->values[] = $params->id;

		return $this->execute($sql, $params->values);
	}

	/**
	 * Retrieves replace values from data.
	 *
	 * @param array|object $data The data for replacement.
	 * @return object An object containing columns and values.
	 */
	protected function getReplaceValues(array|object $data) : object
	{
		$cols = [];
		$values = [];

		if (is_object($data))
		{
			$data = get_object_vars($data);
		}

		if (is_array($data))
		{
			$cols = array_keys($data);
			$values = array_values($data);
		}

		$cols = array_map(static function($col) : string
		{
			return Sanitize::cleanColumn($col);
		}, $cols);

		return (object) [
			'cols'   => $cols,
			'values' => $values,
		];
	}

	/**
	 * Replaces data in a table.
	 *
	 * @param string $tableName The table name.
	 * @param array|object $data The data to replace.
	 * @return bool True on success, false on failure.
	 */
	public function replace(string $tableName, array|object $data) : bool
	{
		$params = $this->getReplaceValues($data);
		$placeholders = $this->setupPlaceholders($params->values);
		$columns = implode(', ', $params->cols);

		$sql = "REPLACE INTO {$tableName} ({$columns}) VALUES ({$placeholders});";

		return $this->execute($sql, $params->values);
	}

	/**
	 * Deletes data from a table.
	 *
	 * @param string $tableName The table name.
	 * @param int|array $id The ID or IDs to delete.
	 * @param string $idColumn The column representing the primary key.
	 * @return bool True on success, false on failure.
	 */
	public function delete(string $tableName, int|array $id, string $idColumn = 'id') : bool
	{
		if (empty($id))
		{
			return false;
		}

		if (is_array($id))
		{
			$placeholders = $this->setupPlaceholders($id);
		}
		else
		{
			$placeholders = '?';
			$id = [$id];
		}

		$sql = "DELETE FROM {$tableName} WHERE {$idColumn} IN ({$placeholders});";

		return $this->execute($sql, $id);
	}

	/**
	 * Generates a LIMIT clause for SQL queries.
	 *
	 * @param int|null $offset The offset value.
	 * @param int|null $count The count value.
	 * @return string The LIMIT clause.
	 */
	protected function getLimit(?int $offset = null, ?int $count = null) : string
	{
		$limit = '';
		if ($offset !== null)
		{
			$limit = " LIMIT {$offset}";
			if ($count !== null)
			{
				$limit .= ", {$count}";
			}
		}
		return $limit;
	}

	/**
	 * Selects data from a table.
	 *
	 * @param string $tableName The table name.
	 * @param string $where The WHERE clause.
	 * @param array|object $params The parameters for the WHERE clause.
	 * @param int|null $offset The offset value.
	 * @param int|null $count The count value.
	 * @return array|bool The fetched results as an array, or false on failure.
	 */
	public function select(
		string $tableName,
		string $where = '',
		array|object $params = [],
		?int $offset = null,
		?int $count = null
	) : array|bool
	{
		$limit = $this->getLimit($offset, $count);
		$whereClause = $where ? "WHERE {$where}" : "";
		$sql = "SELECT * FROM {$tableName} {$whereClause} {$limit};";

		return $this->fetch($sql, $params);
	}

	/**
	 * Fetches results from a prepared statement.
	 *
	 * @param \mysqli_stmt $stmt The prepared statement.
	 * @param string $resultType The type of result: 'object' or 'array'.
	 * @return array The fetched results.
	 */
	protected function fetchStatementResults(\mysqli_stmt $stmt, string $resultType = 'object') : array
	{
		$rows = [];
		$result = $stmt->get_result();
		if ($resultType === 'array')
		{
			while ($row = $result->fetch_array())
			{
				$rows[] = $row;
			}
		}
		else
		{
			while ($row = $result->fetch_object())
			{
				$rows[] = $row;
			}
		}

		$result->free();
		return $rows;
	}

	/**
	 * Creates parameters from data for insert or update queries.
	 *
	 * Extracts column names and values from an array or object. If the column
	 * matching the provided ID column is found and removal is enabled, that value
	 * is stored separately.
	 *
	 * @param array|object $data The input data.
	 * @param string $idColumn The primary key column to exclude.
	 * @param bool $removeId Whether to remove the ID from data.
	 * @return object An object containing 'cols', 'values', and optionally 'id'.
	 */
	protected function createParamsFromData(array|object $data, string $idColumn, bool $removeId = true) : object
	{
		$columns = [];
		$values = [];
		$id = null;

		if (is_object($data))
		{
			$data = get_object_vars($data);
		}

		foreach ($data as $column => $value)
		{
			$cleanColumn = Sanitize::cleanColumn($column);
			if ($removeId && $cleanColumn === Sanitize::cleanColumn($idColumn))
			{
				$id = $value;
				continue;
			}
			$columns[] = $cleanColumn;
			$values[] = $value;
		}

		return (object) [
			'cols'   => $columns,
			'values' => $values,
			'id'     => $id,
		];
	}

	/**
	 * Generates a string of placeholders for SQL queries.
	 *
	 * @param array $params The parameters array.
	 * @return string A string of placeholders.
	 */
	protected function setupPlaceholders(array $params) : string
	{
		return implode(', ', array_fill(0, count($params), '?'));
	}

	/**
	 * Creates update pairs for SQL UPDATE queries.
	 *
	 * Converts an array of column names into a string of column assignments.
	 *
	 * @param object $params The parameters object containing 'cols'.
	 * @return string A string of column assignments for the UPDATE query.
	 */
	protected function setUpdatePairs(object $params) : string
	{
		$pairs = [];
		foreach ($params->cols as $column)
		{
			$cleanColumn = Sanitize::cleanColumn($column);
			$pairs[] = "{$cleanColumn} = ?";
		}
		return implode(', ', $pairs);
	}
}