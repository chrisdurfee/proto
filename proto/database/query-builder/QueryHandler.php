<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder
{
	/**
	 * QueryHandler
	 *
	 * This class serves as a factory and facade for creating query builder objects
	 * (e.g., for SELECT, INSERT, UPDATE, etc.) associated with a specific table.
	 *
	 * @package Proto\Database\QueryBuilder
	 */
	class QueryHandler
	{
		/**
		 * Table name for the query.
		 *
		 * @var string
		 */
		protected string $tableName;

		/**
		 * Table alias for the query.
		 *
		 * @var string|null
		 */
		protected ?string $alias = null;

		/**
		 * Database connection or adapter.
		 *
		 * @var object|null
		 */
		protected ?object $db = null;

		/**
		 * Constructs a new QueryHandler instance.
		 *
		 * @param string $tableName The name of the table.
		 * @param string|null $alias The alias for the table.
		 * @param object|null $db The database connection or adapter.
		 */
		public function __construct(string $tableName, ?string $alias = null, ?object $db = null)
		{
			$this->tableName = $tableName;
			$this->alias = $alias;
			$this->db = $db;
		}

		/**
		 * Creates a new QueryHandler instance for the specified table.
		 *
		 * @param string $tableName The name of the table.
		 * @param string|null $alias The alias for the table.
		 * @param object|null $db The database connection or adapter.
		 *
		 * @return QueryHandler A new QueryHandler instance.
		 */
		public static function table(string $tableName, ?string $alias = null, ?object $db = null) : QueryHandler
		{
			return new static($tableName, $alias, $db);
		}

		/**
		 * Creates a new adapter proxy wrapping the given SQL query builder.
		 *
		 * @param object $sql The SQL query builder object.
		 *
		 * @return AdapterProxy The adapter proxy.
		 */
		protected function createAdapterProxy(object $sql) : AdapterProxy
		{
			return new AdapterProxy($sql, $this->db);
		}

		/**
		 * Creates a SELECT query builder and wraps it in an adapter proxy.
		 *
		 * @param mixed ...$fields One or more fields to select.
		 *
		 * @return AdapterProxy The adapter proxy wrapping the SELECT query builder.
		 */
		public function select(...$fields) : AdapterProxy
		{
			$query = new Select($this->tableName, $this->alias);
			$query->select(...$fields);
			return $this->createAdapterProxy($query);
		}

		/**
		 * Creates a new WITH query builder (Common Table Expression).
		 *
		 * @param string $cteName The name of the CTE.
		 * @param string $query The query string for the CTE.
		 *
		 * @return With The WITH query builder.
		 */
		public static function with(string $cteName, string $query) : With
		{
			return new With($cteName, $query);
		}

		/**
		 * Creates an INSERT query builder and wraps it in an adapter proxy.
		 *
		 * @param array|object|null $data The data to insert.
		 *
		 * @return AdapterProxy The adapter proxy wrapping the INSERT query builder.
		 */
		public function insert(array|object|null $data = null) : AdapterProxy
		{
			$query = new Insert($this->tableName, $this->alias);
			$query->insert($data);
			return $this->createAdapterProxy($query);
		}

		/**
		 * Creates a REPLACE query builder and wraps it in an adapter proxy.
		 *
		 * @param array|object|null $data The data for the replace operation.
		 *
		 * @return AdapterProxy The adapter proxy wrapping the REPLACE query builder.
		 */
		public function replace(array|object|null $data = null) : AdapterProxy
		{
			$query = new Replace($this->tableName, $this->alias);
			$query->replace($data);
			return $this->createAdapterProxy($query);
		}

		/**
		 * Creates an UPDATE query builder and wraps it in an adapter proxy.
		 *
		 * @param array|object|string ...$fields One or more fields or data to update.
		 *
		 * @return AdapterProxy The adapter proxy wrapping the UPDATE query builder.
		 */
		public function update(array|object|string ...$fields) : AdapterProxy
		{
			$query = new Update($this->tableName, $this->alias);
			$query->update(...$fields);
			return $this->createAdapterProxy($query);
		}

		/**
		 * Creates a CREATE query builder.
		 *
		 * @param callable|null $callBack Optional callback to configure the create builder.
		 *
		 * @return Create The CREATE query builder.
		 */
		public function create(?callable $callBack) : Create
		{
			return new Create($this->tableName, $callBack);
		}

		/**
		 * Creates a CREATE VIEW query builder.
		 *
		 * @return CreateView The CREATE VIEW query builder.
		 */
		public function createView() : CreateView
		{
			return new CreateView($this->tableName);
		}

		/**
		 * Creates a DELETE query builder and wraps it in an adapter proxy.
		 *
		 * @return AdapterProxy The adapter proxy wrapping the DELETE query builder.
		 */
		public function delete() : AdapterProxy
		{
			$query = new Delete($this->tableName, $this->alias);
			return $this->createAdapterProxy($query);
		}

		/**
		 * Creates an ALTER TABLE query builder.
		 *
		 * @param callable $callBack A callback to configure the ALTER query builder.
		 *
		 * @return Alter The ALTER TABLE query builder.
		 */
		public function alter(callable $callBack) : Alter
		{
			return new Alter($this->tableName, $callBack);
		}
	}
}

namespace
{
	use Proto\Database\QueryBuilder\QueryHandler;
	use Proto\Database\QueryBuilder\With;

	/**
	 * Creates a new CTE query builder.
	 *
	 * @param string $cteName The name of the CTE.
	 * @param string|object $query The query string or object representing the query.
	 *
	 * @return With The WITH query builder.
	 */
	function With(string $cteName, string|object $query) : With
	{
		if (is_object($query))
		{
			$query = (string)$query;
		}
		return new With($cteName, $query);
	}

	/**
	 * Creates a new QueryHandler instance.
	 *
	 * @param string $tableName The name of the table.
	 * @param string|null $alias The alias for the table.
	 * @param object|null $db The database connection or adapter.
	 *
	 * @return QueryHandler A new QueryHandler instance.
	 */
	function Table(string $tableName, ?string $alias = null, ?object $db = null) : QueryHandler
	{
		return new QueryHandler($tableName, $alias, $db);
	}
}