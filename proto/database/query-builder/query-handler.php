<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder
{
    /**
     * QueryHandler
     *
     * This class will handle queries.
     *
     * @package Proto\Database\QueryBuilder
     */
    class QueryHandler
    {
        /**
         * @var string $tableName
         */
        protected string $tableName;

        /**
         * @var string|null $alias
         */
        protected ?string $alias = null;

        /**
         * @var object|null $db
         */
        protected ?object $db = null;

        /**
         * This will create a new query handler.
         *
         * @param string $tableName
         * @param string|null $alias
         * @param object|null $db
         * @return void
         */
        public function __construct(string $tableName, ?string $alias = null, ?object $db = null)
        {
            $this->tableName = $tableName;
            $this->alias = $alias;
            $this->db = $db;
        }

        /**
         * This will create new query object.
         *
         * @param string $tableName
         * @param string|null $alias
         * @return QueryHandler
         */
        public static function table(string $tableName, ?string $alias = null, ?object $db = null): QueryHandler
        {
            return new static($tableName, $alias, $db);
        }

        /**
         * This will create a new adapter proxy.
         *
         * @param object $sql
         * @return AdapterProxy
         */
        protected function createAdapterProxy(object $sql): AdapterProxy
        {
            return new AdapterProxy($sql, $this->db);
        }

        /**
         * This will create a select query builder.
         *
         * @param mixed ...$fields
         * @return AdapterProxy
         */
        public function select(...$fields): AdapterProxy
        {
            $query = new Select($this->tableName, $this->alias);
            $query->select(...$fields);
            return $this->createAdapterProxy($query);
        }

        /**
         * This will create a new with query builder.
         *
         * @param string $cteName
         * @param string $query
         * @return With
         */
        public static function with(string $cteName, string $query): With
        {
            return new With($cteName, $query);
        }

        /**
         * This will create an insert query builder.
         *
         * @param array|object|null $data
         * @return AdapterProxy
         */
        public function insert($data = null): AdapterProxy
        {
            $query = new Insert($this->tableName, $this->alias);
            $query->insert($data);
            return $this->createAdapterProxy($query);
        }

        /**
         * This will create a replace query builder.
         *
         * @param array|object|null $data
         * @return AdapterProxy
         */
        public function replace($data = null): AdapterProxy
        {
            $query = new Replace($this->tableName, $this->alias);
            $query->replace($data);
            return $this->createAdapterProxy($query);
        }

        /**
         * This will create an update query builder.
         *
         * @param array|object|string ...$fields
         * @return AdapterProxy
         */
        public function update(...$fields): AdapterProxy
        {
            $query = new Update($this->tableName, $this->alias);
            $query->update(...$fields);
            return $this->createAdapterProxy($query);
        }

        /**
         * This will create a new create builder.
         *
         * @param callable|null $callBack
         * @return Create
         */
        public function create(?callable $callBack): Create
        {
            return new Create($this->tableName, $callBack);
        }

        /**
         * This will create a new create builder.
         *
         * @return CreateView
         */
        public function createView(): CreateView
        {
            return new CreateView($this->tableName);
        }

        /**
         * This will create a delete query builder.
         *
         * @return AdapterProxy
         */
        public function delete(): AdapterProxy
        {
            $query = new Delete($this->tableName, $this->alias);
            return $this->createAdapterProxy($query);
        }

        /**
         * This will create an alter table builder.
         *
         * @param callable $callBack
         * @return Alter
         */
        public function alter($callBack): Alter
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
     * This will create a new CTE query builder.
     *
     * @param string $cteName
     * @param string|object $query
     * @return With
     */
	function With(string $cteName, string|object $query): With
	{
        if (is_object($query))
        {
            $query = (string) $query;
        }

		return new With($cteName, $query);
	}

    /**
     * This will create a new query handler.
     *
     * @param string $tableName
     * @param string|null $alias
     * @param object|null $db
     * @return QueryHandler
     */
	function Table(string $tableName, ?string $alias = null, ?object $db = null): QueryHandler
    {
        return new QueryHandler($tableName, $alias, $db);
    }
}