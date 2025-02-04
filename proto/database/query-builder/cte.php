<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Cte
 *
 * This will handle the cte query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Cte extends Template
{
    /**
     * @var object|string|null $query
     */
    protected $query = null;

    /**
     * @var string $recursive
     */
    protected string $recursive = '';

    /**
     * @var string $cteName
     */
    protected string $cteName;

    /**
     * This will create the cte query.
     *
     * @param string $cteName
     * @return void
     */
    public function __construct(string $cteName)
    {
        $this->cteName = $cteName;
    }

    /**
     * This will create a select query builder.
     *
     * @param array|string $tableName
     * @param string|null $alias
     * @return Select
     */
    public function table(array|string $tableName, ?string $alias = null): Select
    {
        $query = new Select($tableName, $alias);
        $this->query = $query;
        return $query;
    }

    /**
     * This will add a query string as the query.
     *
     * @param string $tableName
     * @param string|null $alias
     * @return void
     */
    public function query(string $query): void
    {
        $this->query = $query;
    }

    /**
     * This will add or remove recursive from the cte.
     *
     * @param bool $recursive
     * @return self
     */
    public function recursive(bool $recursive = true): self
    {
        $this->recursive = ($recursive === true)? 'RECURSIVE' : '';

        return $this;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $query = (string)$this->query;

        return "{$this->recursive} {$this->cteName} AS (
            {$query}
        )";
    }
}