<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Config;
use Proto\Tests\Debug;

/**
 * Abstract Adapter Class
 *
 * Provides a base implementation for database connections.
 *
 * @package Proto\Database\Adapters
 * @abstract
 */
abstract class Adapter
{
	/**
	 * @var ConnectionSettings $settings Connection settings instance.
	 */
	protected ConnectionSettings $settings;

	/**
	 * @var object|null $connection Database connection instance.
	 */
	protected ?object $connection = null;

	/**
	 * @var bool $connected Connection status.
	 */
	protected bool $connected = false;

	/**
	 * @var string|null $lastError Stores the last database error.
	 */
	protected ?string $lastError = null;

	/**
	 * @var int|null $lastId Last inserted ID.
	 */
	protected ?int $lastId = null;

	/**
	 * @var bool $caching Enables or disables query caching.
	 */
	protected bool $caching = false;

	/**
	 * Constructor
	 *
	 * @param array|object $settings Raw connection settings.
	 * @param bool $caching Enable or disable caching.
	 */
	public function __construct(array|object $settings, bool $caching = false)
	{
		$this->caching = $caching;
		$this->setSettings($settings);
	}

	/**
	 * Converts and stores the connection settings.
	 *
	 * @param array|object $settings Raw settings data.
	 * @return void
	 */
	protected function setSettings(array|object $settings): void
	{
		$this->settings = new ConnectionSettings($settings);
	}

	/**
	 * Initializes the database connection.
	 *
	 * @abstract
	 * @return bool Connection success.
	 */
	abstract protected function startConnection(): bool;

	/**
	 * Establishes a database connection.
	 *
	 * @return object|null Connection instance or null on failure.
	 */
	protected function connect(): ?object
	{
		if ($this->connected)
		{
			return $this->connection;
		}

		if (!$this->startConnection())
		{
			return null;
		}

		$this->setConnected(true);
		return $this->connection;
	}

	/**
	 * Stops the database connection.
	 *
	 * @abstract
	 * @return void
	 */
	abstract protected function stopConnection(): void;

	/**
	 * Disconnects from the database.
	 *
	 * @return bool Disconnection success.
	 */
	protected function disconnect(): bool
	{
		if (!$this->connected || $this->caching)
		{
			return false;
		}

		$this->stopConnection();
		$this->setConnection(null);
		$this->setConnected(false);

		return true;
	}

	/**
	 * Gets a query builder for a specific table.
	 *
	 * @param string $tableName Table name.
	 * @param string|null $alias Table alias.
	 * @return QueryHandler Query handler instance.
	 */
	public function table(string $tableName, ?string $alias = null): QueryHandler
	{
		return QueryHandler::table($tableName, $alias, $this);
	}

	/**
	 * Sets the database connection instance.
	 *
	 * @param object|null $connection Connection instance.
	 * @return void
	 */
	protected function setConnection(?object $connection): void
	{
		$this->connection = $connection;
	}

	/**
	 * Sets the connection status.
	 *
	 * @param bool $connected Connection status.
	 * @return void
	 */
	protected function setConnected(bool $connected): void
	{
		$this->connected = $connected;
	}

	/**
	 * Executes a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	abstract public function execute(string $sql, array|object $params = []) : bool;

	/**
	 * Fetches the results of a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @param string $resultType The type of results: 'object' or 'array'.
	 * @return array|null The fetched results as an array, or null on failure.
	 */
	abstract public function fetch(string $sql, array|object $params = [], string $resultType = 'object') : ?array;

	/**
	 * Fetches the first result of a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return object|null The first result as an object, or null on failure.
	 */
	abstract public function first(string $sql, array|object $params = []) : ?object;

	/**
	 * Executes a SQL query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	abstract public function query(string $sql, array|object $params = []) : bool;

	/**
	 * Executes a transaction with a single query.
	 *
	 * @param string $sql The SQL query.
	 * @param array|object $params The parameters to bind.
	 * @return bool True on success, false on failure.
	 */
	abstract public function transaction(string $sql, array|object $params = []) : bool;

	/**
	 * Commits a database transaction.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract public function commit() : bool;

	/**
	 * Rolls back a database transaction.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract public function rollback() : bool;

	/**
	 * Insert a record.
	 *
	 * @param object $data Data to insert.
	 * @return bool
	 */
	abstract public function insert(string $tableName, array|object $data) : bool;

	/**
	 * Replace a record.
	 *
	 * @param string $tableName Table name.
	 * @param array|object $data Data to replace.
	 * @return bool
	 */
	abstract public function replace(string $tableName, array|object $data) : bool;

	/**
	 * Updates data in a table.
	 *
	 * @param string $tableName The table name.
	 * @param array|object $data The data to update.
	 * @param string $idColumn The column representing the primary key.
	 * @return bool True on success, false on failure.
	 */
	abstract public function update(string $tableName, array|object $data, string $idColumn = 'id') : bool;

	/**
	 * Deletes data from a table.
	 *
	 * @param string $tableName The table name.
	 * @param int|array|string $id The ID or IDs to delete.
	 * @param string $idColumn The column representing the primary key.
	 * @return bool True on success, false on failure.
	 */
	abstract public function delete(string $tableName, int|array|string $id, string $idColumn = 'id') : bool;

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
	abstract public function select(string $tableName, string $where = '', array|object $params = [], ?int $offset = null, ?int $count = null) : array|bool;

	/**
	 * Logs an error and sets the last error state.
	 *
	 * @param object|string $sql SQL query that caused the error.
	 * @param string|null $errorMessage Error message.
	 * @return void
	 */
	protected function error(object|string $sql, ?string $errorMessage = null): void
	{
		$this->displayError($sql);
		$this->setLastError($errorMessage);
		error(
			$errorMessage . ' query: ' . $sql,
			__FILE__,
			__LINE__
		);
	}

	/**
	 * Sets the last database error.
	 *
	 * @param string|null $errorMessage Error object.
	 * @return void
	 */
	protected function setLastError(?string $errorMessage): void
	{
		if ($errorMessage !== null)
		{
			$this->displayError($errorMessage);
			$this->lastError = $errorMessage;
		}
	}

	/**
	 * Displays the error if error reporting is enabled.
	 *
	 * @param mixed $errorMessage Error data.
	 * @return void
	 */
	protected function displayError(string $errorMessage): void
	{
		if (Config::errors())
		{
			Debug::render($errorMessage);
		}
	}

	/**
	 * Retrieves the last database error.
	 *
	 * @return string|null Last error message or null.
	 */
	public function getLastError(): ?string
	{
		return $this->lastError;
	}

	/**
	 * Sets the last inserted ID.
	 *
	 * @param int $id Inserted record ID.
	 * @return void
	 */
	protected function setLastId(int $id): void
	{
		$this->lastId = $id;
	}

	/**
	 * Retrieves the last inserted ID.
	 *
	 * @return int|null Last inserted ID or null if not set.
	 */
	public function getLastId(): ?int
	{
		return $this->lastId;
	}
}