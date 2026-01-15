<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Database\Database;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Utils\Strings;
use Proto\Database\Adapters\Adapter;

/**
 * TableStorage
 *
 * This class provides a storage implementation for database tables.
 *
 * @package Proto\Storage
 */
class TableStorage implements StorageInterface
{
	/**
	 * Database adapter instance.
	 * @var Adapter
	 */
	protected Adapter $db;

	/**
	 * Connection key.
	 * @var string
	 */
	protected string $connection = 'default';

	/**
	 * Default connection key for all new instances.
	 * @var string|null
	 */
	protected static ?string $defaultConnection = null;

	/**
	 * Last error encountered.
	 * @var \Throwable|null // Store Throwable for better error info
	 */
	protected ?\Throwable $lastError = null;

	/**
	 * Compiled select SQL.
	 * @var string
	 */
	protected static string $compiledSelect;

	/**
	 * Storage constructor.
	 *
	 * @param string $database The database adapter class.
	 */
	public function __construct(
		protected string $database = Database::class
	)
	{
		// Use static default connection if set (for testing), otherwise use 'default'
		if (self::$defaultConnection !== null)
		{
			$this->connection = self::$defaultConnection;
		}

		$this->setConnection();
	}

	/**
	 * Set the default connection for all new Storage instances.
	 * Useful for testing to ensure all operations use the test connection.
	 *
	 * @param string|null $connection Connection name, or null to reset to default
	 * @return void
	 */
	public static function setDefaultConnection(?string $connection): void
	{
		self::$defaultConnection = $connection;
	}

	/**
	 * Set the database adapter class.
	 *
	 * @param string $database
	 * @return void
	 */
	public function setDatabase(string $database): void
	{
		$this->database = $database;
	}

	/**
	 * Establish a database connection.
	 *
	 * @return Adapter|false
	 */
	public function setConnection(): object|false
	{
		$conn = $this->getConnection();

		// Use static getConnection method to ensure proper caching
		// This is critical for test transaction isolation
		$db = $this->database::getConnection($conn, true);

		if (!$db)
		{
			$this->createNewError('Failed to establish database connection');
			return false;
		}

		return $this->db = $db;
	}

	/**
	 * Retrieve the connection key.
	 *
	 * @return string|false
	 */
	protected function getConnection(): string|false
	{
		$conn = $this->connection;
		if (!$conn)
		{
			$this->createNewError('No database connection is set');
		}
		return $conn ?: false;
	}

	/**
	 * Insert data into any table.
	 *
	 * @param string $tableName
	 * @param object $data Data object with keys matching column names.
	 * @return bool
	 */
	public function insertInto(string $tableName, object $data): bool
	{
		$objectVars = get_object_vars($data);
		return $this->table($tableName)
			->insert()
			->fields(array_keys($objectVars))
			/**
			 * @suppresswarnings PHP0418
			 */
			->values(array_fill(0, count($objectVars), '?'))
			->execute(array_values($objectVars));
	}

	/**
	 * Update rows in any table by conditions.
	 *
	 * @param string $tableName
	 * @param array $data Associative array column => newValue.
	 * @param string[] $whereClauses Array of "column = ?" strings.
	 * @param array $params Parameters matching placeholders (appended after data values).
	 * @return bool
	 */
	public function updateTable(string $tableName, array $data, array $whereClauses, array $params): bool
	{
		$cols = [];
		$values = [];
		foreach ($data as $column => $value)
		{
			$cols[] = "`$column` = ?";
			$values[] = $value;
		}

		return $this->table($tableName)
			->update(...$cols)
			->where(...$whereClauses)
			->execute([...$values, ...$params]);
	}

	/**
	 * Delete rows from any table by conditions.
	 *
	 * @param string $tableName
	 * @param string[] $whereClauses Array of "column = ?" strings.
	 * @param array $params Parameters matching placeholders.
	 * @return bool
	 */
	public function deleteFrom(string $tableName, array $whereClauses, array $params): bool
	{
		return $this->table($tableName)
			->delete()
			->where(...$whereClauses)
			->execute($params);
	}

	/**
	 * Set the last error encountered.
	 *
	 * @param \Throwable $error The exception/error object.
	 * @return void
	 */
	protected function setLastError(\Throwable $error): void
	{
		$this->lastError = $error;
	}

	/**
	 * Retrieve the last error encountered by this storage or the underlying adapter.
	 *
	 * @return \Throwable|null
	 */
	public function getLastError(): ?\Throwable
	{
		return $this->lastError ?? new \Exception($this->db->getLastError());
	}

	/**
	 * Create and set a new error.
	 *
	 * @param string $message
	 * @param int|null $code
	 * @return void
	 */
	protected function createNewError(string $message, ?int $code = null): void
	{
		$this->setLastError(new \Exception($message, $code));
	}

	/**
	 * Fetch rows from the database.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return array|false
	 */
	public function fetch(string|object $sql, array $params = []): array|false
	{
		return $this->db->fetch((string)$sql, $params);
	}

	/**
	 * Execute a SQL statement.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return bool
	 */
	public function execute(string|object $sql, array $params = []): bool
	{
		return $this->db->execute((string)$sql, $params);
	}

	/**
	 * Execute a transaction.
	 *
	 * @param string|object $sql SQL or query builder.
	 * @param array $params Parameter values.
	 * @return bool
	 */
	public function transaction(string|object $sql, array $params = []): bool
	{
		return $this->db->transaction((string)$sql, $params);
	}

	/**
	 * Create a query builder for the model table.
	 *
	 * @param string|null $tableName Optional table name.
	 * @param string|null $alias Optional table alias.
	 * @return QueryHandler
	 */
	public function table(?string $tableName = null, ?string $alias = null): QueryHandler
	{
		return $this->db->table($tableName, $alias);
	}

	/**
	 * Create a query builder for a given table.
	 *
	 * @param string $tableName Table name.
	 * @param string|null $alias Table alias.
	 * @return QueryHandler
	 */
	protected function builder(string $tableName, ?string $alias = null): QueryHandler
	{
		return new QueryHandler($tableName, $alias, $this->db);
	}

	/**
	 * Normalize data from snake_case to camelCase.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed
	 */
	public function normalize(mixed $data): mixed
	{
		if (!$data)
		{
			return $data;
		}

		if (is_array($data))
		{
			$rows = [];
			foreach ($data as $row)
			{
				$rows[] = Strings::mapToCamelCase($row);
			}
			return $rows;
		}
		elseif (is_object($data))
		{
			return Strings::mapToCamelCase($data);
		}

		return $data;
	}
}