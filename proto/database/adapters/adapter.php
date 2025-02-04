<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Config;
use Proto\Tests\Debug;

/**
 * Abstract Adapter Class
 *
 * Provides a base implementation for extending
 * to connect to different database types.
 *
 * @package Proto\Database\Adapters
 * @abstract
 */
abstract class Adapter
{
	/**
	 * @var ConnectionSettings $settings
	 */
	protected ConnectionSettings $settings;

	/**
	 * @var object $connection
	 */
	protected $connection;

	/**
	 * @var bool $connected
	 */
	protected bool $connected = false;

	/**
	 * @var object|null $lastError
	 */
	protected ?object $lastError = null;

	/**
	 * @var int|null $lastId
	 */
	protected ?int $lastId = null;

	/**
	 * @var bool $caching
	 */
	protected $caching = false;

	/**
	 * Sets the connection settings upon instantiation.
	 *
	 * @param object $settings
	 * @param bool $caching
	 * @return void
	 */
	public function __construct(
		object $settings,
		bool $caching = false
	)
	{
		$this->caching = $caching;
		$this->setSettings($settings);
	}

	/**
	 * Sets up the connection settings.
	 *
	 * @param object $settings
	 * @return void
	 */
	protected function setSettings(object $settings): void
	{
		$this->settings = new ConnectionSettings($settings);
	}

	/**
	 * Starts the database connection.
	 *
	 * @abstract
	 * @return bool
	 */
	abstract protected function startConnection(): bool;

	/**
	 * Connects to the database.
	 *
	 * @return object|false
	 */
	protected function connect()
	{
		if ($this->connected === true)
		{
			return $this->connection;
		}

		$result = $this->startConnection();
		if ($result === false)
		{
			return false;
		}

		$this->setConnected(true);
		return $this->connection;
	}

	/**
	 * Sets up a query handler to use a query builder.
	 *
	 * @param string $tableName
	 * @param string|null $alias
	 * @return QueryHandler
	 */
	public function table(string $tableName, ?string $alias = null): QueryHandler
	{
		return QueryHandler::table($tableName, $alias, $this);
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
	 * @return bool
	 */
	protected function disconnect(): bool
	{
		if ($this->connected === false)
		{
			return false;
		}

		/**
		 * If caching is enabled, do not disconnect.
		 */
		if ($this->caching === true)
		{
			return true;
		}

		$this->stopConnection();
		$this->setConnection(null);
		$this->setConnected(false);
		return true;
	}

	/**
	 * Sets the connection.
	 *
	 * @param object|null $connection
	 * @return void
	 */
	protected function setConnection($connection): void
	{
		$this->connection = $connection;
	}

	/**
	 * Sets the connection status.
	 *
	 * @param bool $connected
	 * @return void
	 */
	protected function setConnected(bool $connected = true): void
	{
		$this->connected = $connected;
	}

	/**
	 * Sets the last error and displays the SQL and error
	 * if error reporting is enabled.
	 *
	 * @param object|string $sql
	 * @param object|null $error
	 * @return void
	 */
	protected function error(object|string $sql, ?object $error = null): void
	{
		$this->displayError($sql);
		$this->setLastError($error);
	}

	/**
	 * Sets the last error.
	 *
	 * @param object|null $error
	 * @return void
	 */
	protected function setLastError(?object $error = null): void
	{
		if (!$error)
		{
			return;
		}

		$this->displayError($error);
		$this->lastError = $error;
	}

	/**
	 * Displays the error if error reporting is enabled.
	 *
	 * @param mixed $error
	 * @return void
	 */
	protected function displayError(mixed $error): void
	{
		if (Config::errors())
		{
			Debug::render($error);
		}
	}

	/**
	 * Retrieves the last error.
	 *
	 * @return object|null
	 */
	public function getLastError(): ?object
	{
		return $this->lastError;
	}

	/**
	 * Sets the last ID.
	 *
	 * @param int $id
	 * @return void
	 */
	protected function setLastId(int $id): void
	{
		$this->lastId = $id;
	}

	/**
	 * Retrieves the last ID.
	 *
	 * @return int
	 */
	public function getLastId(): int
	{
		return $this->lastId;
	}
}