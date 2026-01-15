<?php declare(strict_types=1);
namespace Proto\Database\Migrations;

use Proto\Database\Database;
use Proto\Database\Adapters\Adapter;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Database\QueryBuilder\Drop;

/**
 * Migration
 *
 * Handles database migrations.
 *
 * @package Proto\Database\Migrations
 * @abstract
 */
abstract class Migration
{
	/**
	 * @var string $connection The database connection name.
	 */
	protected string $connection = 'default';

	/**
	 * @var string $fileName The migration file name.
	 */
	private string $fileName = '';

	/**
	 * @var int|null $id The migration ID (if stored).
	 */
	private ?int $id = null;

	/**
	 * @var array $queries List of migration queries.
	 */
	protected array $queries = [];

	/**
	 * Gets the database connection name.
	 *
	 * @return string The connection name.
	 */
	public function getConnection(): string
	{
		return $this->connection;
	}

	/**
	 * Sets the migration file name.
	 *
	 * @param string $fileName The migration file name.
	 * @return void
	 */
	public function setFileName(string $fileName): void
	{
		$this->fileName = $fileName;
	}

	/**
	 * Gets the migration file name.
	 *
	 * @return string The migration file name.
	 */
	public function getFileName(): string
	{
		return $this->fileName;
	}

	/**
	 * Sets the migration ID.
	 *
	 * @param int $id The migration ID.
	 * @return void
	 */
	public function setId(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * Gets the migration ID.
	 *
	 * @return int|null The migration ID or null if not set.
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Gets the stored migration queries.
	 *
	 * @return array List of queries.
	 */
	public function getQueries(): array
	{
		return $this->queries;
	}

	/**
	 * Gets a database connection.
	 *
	 * @return Adapter|null Database instance or null on failure.
	 */
	protected function db(): ?Adapter
	{
		$db = new Database();
		return $db->connect($this->connection);
	}

	/**
	 * This will fetch data from the database.
	 *
	 * @param string|object $query
	 * @param array $params
	 * @return array|null
	 */
	protected function fetch(string|object $query, array $params = []): ?array
	{
		$db = $this->db();
		return $db->fetch($query, $params);
	}

	/**
	 * This will get the first row from the database.
	 *
	 * @param string|object $query
	 * @param array $params
	 * @return object|null
	 */
	protected function first(string|object $query, array $params = []): ?object
	{
		$db = $this->db();
		return $db->first($query, $params);
	}

	/**
	 * Creates a new query handler.
	 *
	 * @param string $tableName The name of the table.
	 * @return QueryHandler Query handler instance.
	 */
	protected function createQueryHandler(string $tableName): QueryHandler
	{
		return new QueryHandler($tableName);
	}

	/**
	 * Creates a new table migration query.
	 *
	 * @param string $tableName The table name.
	 * @param callable $callback The table schema definition callback.
	 * @return void
	 */
	protected function create(string $tableName, callable $callback): void
	{
		$table = $this->createQueryHandler($tableName);
		$this->queries[] = $table->create($callback);
	}

	/**
	 * Creates a new view migration query.
	 *
	 * @param string $viewName The view name.
	 * @return object The generated query.
	 */
	protected function createView(string $viewName): object
	{
		$table = $this->createQueryHandler($viewName);
		$query = $table->createView();
		$this->queries[] = $query;

		return $query;
	}

	/**
	 * Modifies an existing table structure.
	 *
	 * @param string $tableName The table name.
	 * @param callable $callback The schema modification callback.
	 * @return void
	 */
	protected function alter(string $tableName, callable $callback): void
	{
		$table = $this->createQueryHandler($tableName);
		$this->queries[] = $table->alter($callback);
	}

	/**
	 * Drops a view.
	 *
	 * @param string $viewName The view name.
	 * @return void
	 */
	protected function dropView(string $viewName): void
	{
		$this->drop($viewName, 'view');
	}

	/**
	 * Drops a table or view.
	 *
	 * @param string $tableName The table or view name.
	 * @param string|null $type The type to drop (e.g., 'view').
	 * @return void
	 */
	protected function drop(string $tableName, ?string $type = null): void
	{
		$query = new Drop($tableName);
		if ($type !== null)
		{
			$query->type($type);
		}
		$this->queries[] = $query;
	}

	/**
	 * Inserts data into a table.
	 *
	 * @param string $tableName The table name.
	 * @param array|object $data The data to insert.
	 * @return bool
	 */
	public function insert(string $tableName, array|object $data): bool
	{
		$db = $this->db();
		return $db->insert($tableName, $data);
	}

	/**
	 * Executes a query with optional parameters.
	 *
	 * @param string|object $query The SQL query string or object.
	 * @param array $params The query parameters.
	 * @return bool Query execution success.
	 */
	public function execute(string|object $query, array $params = []): bool
	{
		$db = $this->db();
		return $db->execute((string)$query, $params);
	}

	/**
	 * This will allow you to seed the database with data.
	 *
	 * @return void
	 */
	public function seed(): void
	{

	}

	/**
	 * Runs the migration (must be implemented in subclasses).
	 *
	 * @return void
	 */
	abstract public function up(): void;

	/**
	 * Reverts the migration (must be implemented in subclasses).
	 *
	 * @return void
	 */
	abstract public function down(): void;
}