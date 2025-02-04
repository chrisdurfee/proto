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
 * @package Proto\Database\Adapters
 */
class Mysqli extends Adapter
{
	use MysqliBindTrait;

	/**
	 * Start the database connection.
	 *
	 * @return bool True if the connection is successful, false otherwise.
	 */
	protected function startConnection(): bool
	{
		$settings = $this->settings;
		$connection = new \mysqli('p:' . $settings->host, $settings->username, $settings->password, $settings->database, $settings->port);

		$error = $connection->connect_error;
		if ($error)
		{
			$this->setLastError(new \Exception($error));
			return false;
		}

		$this->setConnection($connection);
		$connection->set_charset('utf8mb4');
		return true;
	}

	/**
	 * Stop the database connection.
	 *
	 * @return void
	 */
	protected function stopConnection(): void
	{
		$this->connection->close();
	}

	/**
	 * Bind statement parameters.
	 *
	 * @param \mysqli_stmt $stmt
	 * @param array|object $params
	 * @return void
	 */
	protected static function bindParams(\mysqli_stmt $stmt, $params = []): void
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
	 * Prepare statement by binding the params array.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return \mysqli_stmt|bool
	 */
	protected function prepare(string $sql, $params = []): \mysqli_stmt|bool
	{
		if ($this->connected === false)
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
	 * This will execute the query.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return bool
	 */
	public function execute(string $sql, $params = []): bool
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
	 * This will prepare and execute a query.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return object|bool
	 */
	protected function prepareAndExecute(string $sql, $params = [])
	{
		$stmt = $this->prepare($sql, $params);
		if (!$stmt)
		{
			return false;
		}

		$result = ($stmt->execute() === true);
		if ($result === false)
		{
			$this->error($sql, $this->connection->error);
			return false;
		}

		return ($result === true)? $stmt : false;
	}

	/**
	 * This will return the results of a query.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @param string $resultType
	 * @return array|bool
	 */
	public function fetch(string $sql, $params = [], string $resultType = 'object')
	{
		$db = $this->connect();
		if (!$db)
		{
			return false;
		}

		$rows = [];
		$stmt = $this->prepareAndExecute($sql, $params);
		if ($stmt)
		{
			$rows = $this->fetchStatementResults($stmt, $resultType);
			$stmt->close();
		}

		$this->disconnect();
		return $rows;
	}

	/**
	 * This will execute a query.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return bool
	 */
	public function query(string $sql, $params = []): bool
	{
		return $this->execute($sql, $params);
	}

	/**
	 * This will set the state of autocommit.
	 *
	 * @param bool $enable
	 * @return void
	 */
	public function autoCommit(bool $enable): void
	{
		if ($this->connected === false)
		{
			return;
		}

		$this->connection->autocommit($enable);
	}

	/**
	 * This will start a trasaction.
	 *
	 * @return bool
	 */
	public function beginTransaction(): bool
	{
		if ($this->connected === false)
		{
			return false;
		}

		$result = $this->connection->begin_transaction();
		return $this->checkResult($result);
	}

	/**
	 * This will set the last error if the result is false.
	 *
	 * @param bool $result
	 * @return bool
	 */
	protected function checkResult(bool $result): bool
	{
		if (!$result)
		{
			$this->setLastError($this->connection->error);
		}
		return $result;
	}

	/**
	 * This will create a transaction.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return bool
	 */
	public function transaction(string $sql, $params = []): bool
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
	 * This will commit a transaction.
	 *
	 * @return bool
	 */
	public function commit(): bool
	{
		if ($this->connected === false)
		{
			return false;
		}

		$result = $this->connection->commit();
		return $this->checkResult($result);
	}

	/**
	 * This will rollback a transaction.
	 *
	 * @return bool
	 */
	public function rollback(): bool
	{
		if ($this->connected === false)
		{
			return false;
		}

		$result = $this->connection->rollback();
		return $this->checkResult($result);
	}

	/**
	 * This will insert into a table.
	 *
	 * @param string $tableName
	 * @param array|object $data
	 * @return bool
	 */
	public function insert(string $tableName, $data): bool
	{
		$params = $this->createParamsFromData($data, 'id', true);

		/* this will setup  the insert column names
		and convert it to a string */
		$cols = $params->cols;
		$columns = implode(', ', $cols);

		/* this will setup the values placeholder and
		convert it to a string */
		$values = $this->setupPlaceholders($cols);

		$sql = "INSERT INTO {$tableName}
			({$columns})
		VALUES
			({$values});";

		return $this->execute($sql, $params->values);
	}

	/**
	 * This will update a table.
	 *
	 * @param string $tableName
	 * @param array|object $data
	 * @param string $idColumn
	 * @return bool
	 */
	public function update(string $tableName, $data, string $idColumn = 'id'): bool
	{
		$params = $this->createParamsFromData($data, $idColumn, true);
		$update = $this->setUpdatePairs($params);

		/* this will check to stop any query that doesn't
		have an id or set columns to update. */
		if (!$update)
		{
			return false;
		}

		$idColumn = Sanitize::cleanColumn($idColumn);
		$sql = "UPDATE {$tableName} SET
			{$update}
		WHERE
			{$idColumn} = ?;";

		/* we need to add the id to the end of the
		values array to add it to the params */
		array_push($params->values, $params->id);
		return $this->execute($sql, $params->values);
	}

	/**
	 * This will create the replace column values.
	 *
	 * @param array|object $data
	 * @return object
	 */
	protected function getReplaceValues(array|object $data): object
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

		/**
		 * This will clean the column names.
		 */
		$cols = array_map(function($col)
		{
			return Sanitize::cleanColumn($col);
		}, $cols);

		return (object) [
			'cols' => $cols,
			'values' => $values
		];
	}

	/**
	 * This will replace into a table.
	 *
	 * @param string $tableName
	 * @param array|object $data
	 * @return bool
	 */
	public function replace(string $tableName, object|array $data): bool
	{
		$params = $this->getReplaceValues($data);

		/* this will setup the values placeholder and
		convert it to a string */
		$values = $this->setupPlaceholders($params->values);
		$cols = implode(', ', $params->cols);

		$sql = "REPLACE INTO {$tableName}
					({$cols})
				VALUES
					({$values});";

		return $this->execute($sql, $params->values);
	}

	/**
	 * This will delete from a table.
	 *
	 * @param string $tableName
	 * @param array|int $id
	 * @param string $idColumn
	 * @return bool
	 */
	public function delete($tableName, $id, string $idColumn = 'id'): bool
	{
		if (is_null($id))
		{
			return false;
		}

		if (is_array($id))
		{
			/* this will setup the values placehoder and
			convert itto  a string */
			$values = $this->setupPlaceholders($id);
		}
		else
		{
			$values = '?';
			$id = [$id];
		}

		$sql = "DELETE FROM {$tableName}
		WHERE
			{$idColumn} IN ({$values});";

		return $this->execute($sql, $id);
	}

	/**
	 * This will get a string used to limit a query.
	 *
	 * @param int|null $offset
	 * @param int|null $count
	 * @return string
	 */
	protected function getLimit(?int $offset = null, ?int $count = null): string
	{
		$limit = '';
		if (!is_null($offset))
		{
			$int = (int)$offset;
			if (!is_numeric($int))
			{
				return $limit;
			}

			$limit .= " LIMIT " . $int;

			if (!is_null($count))
			{
				$int = (int)$count;
				if( !is_numeric($int))
				{
					return $limit;
				}

				$limit .= ", " . $int;
			}
		}
		return $limit;
	}

	/**
	 * This will select from a table.
	 *
	 * @param string $tableName
	 * @param string $where
	 * @param array|object $params
	 * @param int $offset
	 * @param int $count
	 * @return array|bool
	 */
	public function select(
		string $tableName,
		string $where = '',
		$params = [],
		int $offset = null,
		int $count = null
	)
	{
		$limit = $this->getLimit($offset, $count);
		$sql = "SELECT
			*
		FROM
			{$tableName}
		WHERE
			{$where}
		{$limit}
			;";

		return $this->fetch($sql, $params);
	}

	/**
	 * This will return results from a statement.
	 *
	 * @param object $stmt
	 * @param string $resultType
	 * @return array
	 */
	protected function fetchStatementResults($stmt, string $resultType = 'object'): array
	{
		$rows = [];

		$result = $stmt->get_result();
		if ($resultType === 'array')
		{
			while ($row = $result->fetch_array())
			{
				array_push($rows, $row);
			}
		}
		else
		{
			while ($row = $result->fetch_object())
			{
				array_push($rows, $row);
			}
		}

		$result->free();
		return $rows;
	}
}