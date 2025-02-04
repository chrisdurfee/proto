<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * With
 *
 * This will create a with statement.
 *
 * @package Proto\Database\QueryBuilder
 */
class With extends Template
{
    /**
     * @var string|null $recursive
     */
    protected ?string $recursive;

    /**
     * @var array $cteTables
     */
    protected array $cteTables = [];

    /**
     * @var AdapterProxy $query
     */
    protected AdapterProxy $query;

    /**
     * This will create a Common Table Expression.
     *
     * @param string $cteName
     * @param string $query
     * @return void
     */
    public function __construct(string $cteName, string $query)
    {
        $this->cte($cteName, $query);
    }

    /**
     * This will create a cte.
     *
     * @param string $cteName
     * @param string $query
     * @return self
     */
    public function cte(string $cteName, string $query): self
    {
        $cte = new Cte($cteName);
        $cte->query($query);
        $sql = (string)$cte;

        array_push($this->cteTables, $sql);
        return $this;
    }

    /**
     * This will add a query string as the select.
     *
     * @param string $tableName
     * @param string|null $alias
     * @return self
     */
    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * This will create a query builder.
     *
     * @param array|string $tableName
     * @param string|null $alias
     * @return AdapterProxy
     */
    public function select(array|string $tableName, ?string $alias = null): AdapterProxy
    {
        $query = new QueryHandler($tableName, $alias);
        $select = $query->select();
        $this->query = $select;
        return $select;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $cteTables = join(',', $this->cteTables);

        $query = (string)$this->query;
        return "WITH {$cteTables} {$query}";
    }
}